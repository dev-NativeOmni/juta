<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Support\HafalanOrder;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Membuat & memperbarui target hafalan otomatis per murid per bulan untuk kelas
 * 11 dan 12 (Kelas 10 / UMMI memakai target buatan guru).
 *
 * Titik awal = surah & ayat pertama yang disetorkan murid di term tersebut.
 * Target akhir tiap bulan = titik awal + total baris target sampai bulan itu
 * (jumlah pertemuan terjadwal x baris per level), dihitung menurut urutan hafalan
 * sekolah (Juz 30 fleksibel, lalu Juz 29, 28, ... dari awal juz; lihat termPlan()).
 *
 * Target buatan guru selalu menang: bulan yang sudah punya target guru tidak dibuat
 * target otomatis, target otomatis yang diedit guru menjadi target guru, dan target
 * otomatis yang dihapus guru tidak dibuat ulang.
 */
class AutoHafalanTargetService
{
    public function __construct(
        private readonly AcademicCalendarService $calendar,
        private readonly QuranLineTargetService $quran,
        private readonly HafalanTargetAutoCompletionService $completion,
        private readonly HafalanProgressService $progress,
    ) {}

    /**
     * Baris per pertemuan (Pengaturan Target Hafalan); null untuk Ummi.
     */
    public static function levelBaris(?string $tahfizhLevel): ?int
    {
        return TargetRules::linesForLevel($tahfizhLevel);
    }

    /**
     * Sinkronkan target otomatis satu murid untuk term yang memuat $date.
     */
    public function syncStudent(Student $student, Carbon $date): void
    {
        $student->loadMissing('classRoom.program');
        $classRoom = $student->classRoom;
        $termStart = $this->calendar->termStartDate($date);
        $months = $this->calendar->termMonths($date);
        $termEnd = end($months)['end'];

        $desired = $this->desiredTargets($student, $classRoom, $months, $termStart, $termEnd);

        $autoRows = HafalanTarget::withTrashed()
            ->where('student_id', $student->id)
            ->whereIn('auto_month', array_keys($months))
            ->get()
            ->keyBy('auto_month');

        $manualMonths = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereNull('auto_month')
            ->whereBetween('target_date', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->pluck('target_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->all();

        foreach ($months as $monthKey => $range) {
            $row = $autoRows->get($monthKey);

            // Bulan yang sudah punya target guru tidak diisi target otomatis.
            $position = in_array($monthKey, $manualMonths, true) ? null : ($desired[$monthKey] ?? null);

            if ($position === null) {
                if ($row && ! $row->trashed()) {
                    $row->forceDelete();
                }

                continue;
            }

            // Target otomatis yang dihapus guru tidak dibuat ulang.
            if ($row?->trashed()) {
                continue;
            }

            $attributes = [
                'surah_id' => $position['surah']->id,
                'ayah' => $position['ayah_end'],
                'target_date' => $range['end']->toDateString(),
            ];

            if ($row === null) {
                if (! $student->teacher_id) {
                    continue;
                }

                $row = HafalanTarget::create($attributes + [
                    'student_id' => $student->id,
                    'teacher_id' => $student->teacher_id,
                    'status' => 'active',
                    'auto_month' => $monthKey,
                    'notes' => 'Target otomatis (jumlah pertemuan x level, dari setoran pertama term).',
                ]);
            } elseif ($row->surah_id !== $attributes['surah_id']
                || (int) $row->ayah !== $attributes['ayah']
                || $row->target_date?->toDateString() !== $attributes['target_date']) {
                $row->fill($attributes);
                $row->status = 'active';
                $row->completed_at = null;
                $row->save();
            } else {
                // Tidak ada perubahan posisi: status tetap (mis. sudah completed).
                continue;
            }

            // Selesai bila semua ayat dari titik awal triwulan sampai target ini sudah lulus disetor.
            $evaluation = $this->progress->evaluate($student, (int) $position['surah']->number, (int) $position['ayah_end'], $termStart, $termEnd, now());
            if ($evaluation['reached']) {
                $row->update(['status' => 'completed', 'completed_at' => now()]);
            }
        }
    }

    /**
     * Sinkronkan semua murid aktif kelas 11/12 dalam satu kelas.
     */
    public function syncClass(ClassRoom $classRoom, Carbon $date): void
    {
        if ($classRoom->isGradeTen()) {
            return;
        }

        Student::query()
            ->where('class_room_id', $classRoom->id)
            ->where('status', 'active')
            ->get()
            ->each(function (Student $student) use ($date, $classRoom) {
                $student->setRelation('classRoom', $classRoom);
                $this->syncStudent($student, $date);
            });
    }

    /**
     * Posisi target akhir tiap bulan, atau array kosong bila murid tidak berhak/tidak punya titik awal.
     *
     * @return array<string, array{surah: Surah, ayah_start: int, ayah_end: int}>
     */
    private function desiredTargets(Student $student, ?ClassRoom $classRoom, array $months, Carbon $termStart, Carbon $termEnd): array
    {
        $plan = $this->termPlan($student, $termStart, $classRoom);

        return collect($plan['months'])
            ->filter(fn ($month) => $month['auto_position'] !== null)
            ->map(fn ($month) => $month['auto_position'])
            ->all();
    }

    /**
     * Rencana target satu triwulan untuk satu murid (dipakai target bulanan otomatis dan
     * halaman Target Triwulan): titik awal = setoran pertama triwulan, target = titik awal +
     * (pertemuan aktif x baris per level), dihitung menurut urutan hafalan sekolah
     * (Juz 30 fleksibel, lalu 29-27 dari awal juz, lalu sesuai arah murid). Target bulan = titik antara.
     *
     * @return array{
     *     eligible: bool, reason: ?string, level_baris: ?int,
     *     start: ?array{surah: Surah, ayah: int, date: ?string},
     *     months: array<string, array{label: string, meetings: int, cumulative_lines: int, position: ?array}>,
     *     term_meetings: int, target_lines: int, target: ?array,
     *     capaian: ?array{surah: Surah, ayah: int, date: ?string}, achieved_lines: float, progress: int, reached: bool
     * }
     */
    public function termPlan(Student $student, Carbon $date, ?ClassRoom $classRoom = null): array
    {
        $classRoom ??= $student->classRoom;
        $termStart = $this->calendar->termStartDate($date);
        $months = $this->calendar->termMonths($date);
        $termEnd = end($months)['end'];
        $levelBaris = self::levelBaris($student->tahfizh_level);

        $plan = [
            'eligible' => false, 'reason' => null, 'level_baris' => $levelBaris, 'start' => null,
            'months' => [], 'term_meetings' => 0, 'target_lines' => 0, 'target' => null,
            'capaian' => null, 'achieved_lines' => 0.0, 'progress' => 0, 'reached' => false, 'juz_orders' => [],
            'juz_order_source' => fn (int $juz) => 'default', 'target_source' => 'computed',
        ];

        if (! $classRoom) {
            return ['reason' => 'no_class'] + $plan;
        }
        if ($classRoom->isGradeTen() || $levelBaris === null) {
            return ['reason' => 'ummi'] + $plan;
        }

        // Pertemuan aktif per bulan (jadwal kelas pekanan x kalender) -> baris kumulatif.
        $cumulative = 0;
        foreach ($months as $monthKey => $range) {
            $meetings = $this->calendar->scheduledMeetings($classRoom, $range['start'], $range['end']);
            $cumulative += $levelBaris * $meetings;
            $plan['months'][$monthKey] = [
                'label' => $range['start']->locale('id')->translatedFormat('F Y'),
                'meetings' => $meetings,
                'cumulative_lines' => $cumulative,
                'position' => null,
                'auto_position' => null,
                'source' => 'computed',
            ];
            $plan['term_meetings'] += $meetings;
        }
        $plan['target_lines'] = $cumulative;

        $records = $this->progress->records($student);
        $first = $this->progress->firstBetween($records, $termStart, $termEnd);
        if (! $first) {
            return ['eligible' => true, 'reason' => 'no_start'] + $plan;
        }

        $surahs = $this->progress->surahs();
        $startSurah = (int) $first->surah_number;
        $startAyah = (int) $first->ayah_start;
        $direction = $student->hafalan_direction;
        $juzOrders = $this->progress->juzOrders($student, $records);
        // Ayat yang sudah dihafal sebelum triwulan dilewati (semua juz fleksibel).
        $coveredBefore = $this->progress->coverage($records, $termStart);

        $plan['eligible'] = true;
        $plan['juz_orders'] = $juzOrders;
        $manualJuz = array_map('intval', array_keys($student->juz_orders ?? []));
        $detected = $this->progress->detectedJuzOrders($records);
        $plan['juz_order_source'] = fn (int $juz) => in_array($juz, $manualJuz, true) ? 'manual' : (isset($detected[$juz]) ? 'auto' : 'default');
        $plan['start'] = [
            'surah' => $surahs->get($startSurah),
            'ayah' => $startAyah,
            'juz' => HafalanOrder::juzOf($startSurah, $startAyah),
            'date' => Carbon::parse($first->submitted_at)->toDateString(),
        ];

        foreach ($plan['months'] as $monthKey => $month) {
            if ($month['meetings'] > 0) {
                $position = $this->quran->targetPosition($startSurah, $startAyah, (float) $month['cumulative_lines'], $surahs, $coveredBefore, $direction, $juzOrders);
                $plan['months'][$monthKey]['auto_position'] = $position;
                $plan['months'][$monthKey]['position'] = $position;
            }
        }

        $walk = $this->quran->walkLines($startSurah, $startAyah, (float) $plan['target_lines'], $surahs, $coveredBefore, $direction, $juzOrders);
        $plan['target'] = $walk['position'] ?? null;

        // Target tersimpan menang atas hitungan (sama dengan yang dipakai laporan): target guru di
        // suatu bulan menggantikan titik bulan itu; target triwulan = target tersimpan terakhir.
        $stored = $this->storedTermTargets($student, $termStart, $termEnd);
        foreach ($stored->whereNull('auto_month') as $manual) {
            $monthKey = $manual->target_date->format('Y-m');
            if (isset($plan['months'][$monthKey]) && $manual->surah) {
                $plan['months'][$monthKey]['position'] = ['surah' => $manual->surah, 'ayah_start' => 1, 'ayah_end' => (int) $manual->ayah];
                $plan['months'][$monthKey]['source'] = 'manual';
            }
        }
        $latestStored = $stored->last();
        if ($latestStored?->surah) {
            $plan['target'] = ['surah' => $latestStored->surah, 'ayah_start' => 1, 'ayah_end' => (int) $latestStored->ayah];
            $plan['target_source'] = $latestStored->auto_month === null ? 'manual' : 'auto';
        }

        // Capaian ditampilkan = setoran lulus terakhir di triwulan ini; progres & tuntas dari cakupan ayat.
        $latest = $records
            ->filter(fn ($r) => $r->status === 'passed' && Carbon::parse($r->submitted_at)->betweenIncluded($termStart, $termEnd->copy()->endOfDay()))
            ->last();
        if ($latest) {
            $plan['capaian'] = [
                'surah' => $surahs->get((int) $latest->surah_number),
                'ayah' => (int) $latest->ayah_end,
                'date' => Carbon::parse($latest->submitted_at)->toDateString(),
            ];
        }

        if ($plan['target']) {
            // Nilai target yang berlaku (tersimpan atau hitungan) dengan aturan cakupan yang sama dengan laporan.
            $evaluation = $this->progress->evaluate($student, (int) $plan['target']['surah']->number, (int) $plan['target']['ayah_end'], $termStart, $termEnd, now(), $records);
            $coverageNow = $this->progress->coverage($records);
            $plan['reached'] = $evaluation['reached'];
            $plan['progress'] = $evaluation['progress'];
            $plan['achieved_lines'] = $evaluation['pieces'] !== null
                ? round($this->quran->piecesLines($evaluation['pieces'], $surahs, $coverageNow), 1)
                : ($evaluation['reached'] ? (float) $plan['target_lines'] : 0.0);
        }

        return $plan;
    }

    /**
     * Target tersimpan (tidak dihapus) di dalam triwulan, urut tanggal -- sumber yang sama dengan laporan.
     */
    public function storedTermTargets(Student $student, Carbon $termStart, Carbon $termEnd): Collection
    {
        return HafalanTarget::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->whereBetween('target_date', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->orderBy('target_date')
            ->orderBy('id')
            ->get();
    }
}
