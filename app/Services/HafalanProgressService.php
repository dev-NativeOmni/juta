<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Models\Surah;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Progres hafalan satu murid menurut urutan hafalan sekolah -- satu sumber untuk
 * target otomatis, Target Triwulan, Laporan Triwulan, Laporan Periodik, dan Wali Kelas:
 *
 * - Cakupan: ayat yang sudah lulus disetor (urutan bebas).
 * - Urutan di dalam juz: dideteksi dari setoran (surah makin kecil = dari akhir juz),
 *   bisa dikoreksi guru (Student::juz_orders).
 * - Tuntas: SEMUA ayat dari titik awal triwulan sampai posisi target sudah lulus disetor.
 */
class HafalanProgressService
{
    private ?Collection $surahsByNumber = null;

    public function __construct(private readonly QuranLineTargetService $quran) {}

    public function surahs(): Collection
    {
        return $this->surahsByNumber ??= Surah::query()->get()->keyBy('number');
    }

    /**
     * Semua setoran murid (semua status), urut waktu: surah_number, ayah_start, ayah_end, status, submitted_at.
     */
    public function records(Student $student): Collection
    {
        return HafalanRecordSurah::query()
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->join('surahs', 'surahs.id', '=', 'hafalan_record_surahs.surah_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $student->id)
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_records.id')
            ->orderBy('hafalan_record_surahs.sort_order')
            ->orderBy('hafalan_record_surahs.id')
            ->get([
                'surahs.number as surah_number', 'hafalan_record_surahs.ayah_start', 'hafalan_record_surahs.ayah_end',
                'hafalan_record_surahs.status', 'hafalan_records.submitted_at',
            ]);
    }

    /**
     * Cakupan ayat lulus dengan batas waktu: $before (eksklusif, tanggal) dan/atau $until (inklusif, sampai akhir hari).
     *
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    public function coverage(Collection $records, ?Carbon $before = null, ?Carbon $until = null): array
    {
        return AyahCoverage::fromRanges(
            $records
                ->filter(fn ($r) => $r->status === 'passed')
                ->filter(fn ($r) => $before === null || Carbon::parse($r->submitted_at)->lt($before->copy()->startOfDay()))
                ->filter(fn ($r) => $until === null || Carbon::parse($r->submitted_at)->lte($until->copy()->endOfDay()))
                ->map(fn ($r) => [(int) $r->surah_number, (int) $r->ayah_start, (int) $r->ayah_end])
        );
    }

    /**
     * Urutan di dalam juz hasil deteksi setoran: juz => 'asc'|'desc'. Juz dengan kurang
     * dari dua surah berbeda tidak dideteksi (memakai default / koreksi guru).
     *
     * @return array<int, string>
     */
    public function detectedJuzOrders(Collection $records): array
    {
        $surahsByJuz = [];
        foreach ($records->where('status', 'passed') as $record) {
            $juz = HafalanOrder::juzOf((int) $record->surah_number, (int) $record->ayah_start);
            $surahsByJuz[$juz] ??= [];
            if (! in_array((int) $record->surah_number, $surahsByJuz[$juz], true)) {
                $surahsByJuz[$juz][] = (int) $record->surah_number;
            }
        }

        $orders = [];
        foreach ($surahsByJuz as $juz => $surahs) {
            if (count($surahs) >= 2) {
                $orders[$juz] = end($surahs) < $surahs[0] ? HafalanOrder::DESC : HafalanOrder::ASC;
            }
        }

        return $orders;
    }

    /**
     * Urutan di dalam juz yang dipakai: koreksi guru > deteksi setoran > default.
     *
     * @return array<int, string>
     */
    public function juzOrders(Student $student, Collection $records): array
    {
        $manual = collect($student->juz_orders ?? [])
            ->filter(fn ($order) => in_array($order, [HafalanOrder::ASC, HafalanOrder::DESC], true))
            ->mapWithKeys(fn ($order, $juz) => [(int) $juz => $order])
            ->all();

        return $manual + $this->detectedJuzOrders($records);
    }

    /**
     * Setoran pertama pada rentang tanggal (titik awal triwulan).
     */
    public function firstBetween(Collection $records, Carbon $start, Carbon $end): mixed
    {
        return $records->first(fn ($r) => Carbon::parse($r->submitted_at)->betweenIncluded($start->copy()->startOfDay(), $end->copy()->endOfDay()));
    }

    /**
     * Nilai apakah target posisi (surah, ayat) sudah tuntas pada $cutoff: semua ayat dari
     * titik awal triwulan sampai target sudah lulus disetor.
     *
     * @return array{reached: bool, progress: int, pieces: ?array}
     */
    public function evaluate(Student $student, int $targetSurah, int $targetAyah, Carbon $termStart, Carbon $termEnd, Carbon $cutoff, ?Collection $records = null): array
    {
        $records ??= $this->records($student);
        $coverageNow = $this->coverage($records, null, $cutoff);
        $first = $this->firstBetween($records, $termStart, $termEnd);

        // Target yang ayatnya sudah dihafal sebelum triwulan: tuntas bila tetap tercakup.
        if (! $first) {
            $reached = AyahCoverage::contains($coverageNow[$targetSurah] ?? [], $targetAyah);

            return ['reached' => $reached, 'progress' => $reached ? 100 : 0, 'pieces' => null];
        }

        $pieces = $this->quran->piecesUntil(
            (int) $first->surah_number, (int) $first->ayah_start, $targetSurah, $targetAyah, $this->surahs(),
            $this->coverage($records, $termStart), $student->hafalan_direction, $this->juzOrders($student, $records)
        );

        if ($pieces === null) {
            $reached = AyahCoverage::contains($coverageNow[$targetSurah] ?? [], $targetAyah);

            return ['reached' => $reached, 'progress' => $reached ? 100 : 0, 'pieces' => null];
        }

        return [
            'reached' => $this->quran->piecesCovered($pieces, $coverageNow),
            'progress' => $this->progress($pieces, $coverageNow),
            'pieces' => $pieces,
        ];
    }

    /**
     * Persentase baris potongan target yang sudah tercakup.
     */
    public function progress(array $pieces, array $coverage): int
    {
        $total = $this->quran->piecesLines($pieces, $this->surahs());

        return $total > 0 ? (int) min(100, round($this->quran->piecesLines($pieces, $this->surahs(), $coverage) / $total * 100)) : 0;
    }

    // ── Ringkasan hafalan keseluruhan (method asli, dipertahankan) ──

    public function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    public function memorizedAyahCount(Student $student): int
    {
        $recordsBySurah = HafalanRecordSurah::query()
            ->whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
            ->where('status', 'passed')
            ->select([
                'surah_id',
                'ayah_start',
                'ayah_end',
            ])
            ->get()
            ->groupBy('surah_id');

        return $recordsBySurah->sum(function (Collection $records) {
            return $this->mergeAndCountRanges($records);
        });
    }

    public function progressPercentage(Student $student): float
    {
        $totalAyahs = $this->totalQuranAyahs();

        if ($totalAyahs <= 0) {
            return 0;
        }

        return round(($this->memorizedAyahCount($student) / $totalAyahs) * 100, 2);
    }

    public function summary(Student $student): array
    {
        $memorizedAyahCount = $this->memorizedAyahCount($student);
        $totalAyahCount = $this->totalQuranAyahs();

        return [
            'memorized_ayah_count' => $memorizedAyahCount,
            'total_ayah_count' => $totalAyahCount,
            'progress_percentage' => $totalAyahCount > 0
                ? round(($memorizedAyahCount / $totalAyahCount) * 100, 2)
                : 0,
            'total_hafalan_records' => $student->hafalanRecords()->count(),
            'total_murajaah_records' => $student->murajaahRecords()->count(),
            'latest_hafalan' => $student->hafalanRecords()
                ->with([
                    'surahs.surah',
                    'teacher.user',
                ])
                ->latest('submitted_at')
                ->latest()
                ->first(),
            'latest_murajaah' => $student->murajaahRecords()
                ->with([
                    'surah',
                    'teacher.user',
                ])
                ->latest('reviewed_at')
                ->latest()
                ->first(),
        ];
    }

    private function mergeAndCountRanges(Collection $records): int
    {
        $ranges = $records
            ->map(function ($record) {
                return [
                    'start' => (int) $record->ayah_start,
                    'end' => (int) $record->ayah_end,
                ];
            })
            ->sortBy('start')
            ->values();

        if ($ranges->isEmpty()) {
            return 0;
        }

        $total = 0;
        $currentStart = null;
        $currentEnd = null;

        foreach ($ranges as $range) {
            $start = $range['start'];
            $end = $range['end'];

            if ($currentStart === null) {
                $currentStart = $start;
                $currentEnd = $end;

                continue;
            }

            if ($start <= $currentEnd + 1) {
                $currentEnd = max($currentEnd, $end);

                continue;
            }

            $total += ($currentEnd - $currentStart + 1);

            $currentStart = $start;
            $currentEnd = $end;
        }

        if ($currentStart !== null && $currentEnd !== null) {
            $total += ($currentEnd - $currentStart + 1);
        }

        return $total;
    }
}
