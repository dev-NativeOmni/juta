<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\CalendarMonthLock;
use App\Models\ClassRoom;
use App\Models\ClassWeekSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Satu-satunya sumber aturan hari efektif sekolah (dipakai lewat app(SchoolCalendar::class),
 * terdaftar sebagai scoped singleton supaya cache per request/tes tidak bocor).
 *
 * Tahfizh efektif = hari ada di jadwal kelas pekan itu (jadwal khusus/arsip pekan di
 *                   class_week_schedules, selain itu tahfizh_days) DAN bukan libur Tahfizh
 *                   (global atau khusus kelas itu).
 * Adab efektif    = hari pengisian Adab (Kalender, default Selasa-Jumat) DAN bukan libur Adab global.
 *
 * Tahun yang belum pernah diatur admin memakai DEFAULT_TOTAL_HOLIDAYS sebagai Libur Total.
 */
class SchoolCalendar
{
    public const SCOPE_TAHFIZH = 'tahfizh';

    public const SCOPE_ADAB = 'adab';

    /** Hari kuisioner Adab bawaan: Selasa-Jumat (ISO); bisa diubah di Kalender (adabDays()). */
    public const ADAB_DAYS = [2, 3, 4, 5];

    public const DAY_NAMES = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /** @var array<int, int>|null */
    private ?array $adabDaysCache = null;

    /** Libur Total bawaan untuk tahun yang belum diatur ('m-d'). */
    private const DEFAULT_TOTAL_HOLIDAYS = ['01-01', '05-01', '06-01', '08-17', '12-25'];

    /** @var array<int, array<string, array{tahfizh_off: bool, adab_off: bool}>> */
    private array $globalCache = [];

    /** @var array<int, array<string, array<int, int>>> */
    private array $classCache = [];

    /** @var array<string, array<string, true>> */
    private array $adabDatesCache = [];

    /** @var array<string, array<int, string>> */
    private array $lockCache = [];

    /** @var array<int, array<string, ClassWeekSchedule>> */
    private array $weekCache = [];

    public function __construct()
    {
        // Instance baru = request/tes baru: jangan pakai skor adab dari kalender sebelumnya.
        Setting::flushCalendarCaches();
    }

    /**
     * Status libur untuk semua kelas: 'Y-m-d' => [tahfizh_off, adab_off].
     *
     * @return array<string, array{tahfizh_off: bool, adab_off: bool}>
     */
    public function globalDays(int $year): array
    {
        if (isset($this->globalCache[$year])) {
            return $this->globalCache[$year];
        }

        $days = CalendarDay::query()
            ->whereNull('class_room_id')
            ->whereYear('date', $year)
            ->get()
            ->mapWithKeys(fn (CalendarDay $day) => [$day->date->toDateString() => [
                'tahfizh_off' => $day->tahfizh_off,
                'adab_off' => $day->adab_off,
            ]])
            ->all();

        if ($days === [] && ! $this->isYearConfigured($year)) {
            foreach (self::DEFAULT_TOTAL_HOLIDAYS as $monthDay) {
                $days["{$year}-{$monthDay}"] = ['tahfizh_off' => true, 'adab_off' => true];
            }
        }

        return $this->globalCache[$year] = $days;
    }

    /**
     * Libur Tahfizh khusus kelas (Libur Sebagian): 'Y-m-d' => [class_room_id, ...].
     *
     * @return array<string, array<int, int>>
     */
    public function classDays(int $year): array
    {
        if (isset($this->classCache[$year])) {
            return $this->classCache[$year];
        }

        $days = [];
        CalendarDay::query()
            ->whereNotNull('class_room_id')
            ->whereYear('date', $year)
            ->where('tahfizh_off', true)
            ->orderBy('class_room_id')
            ->get(['date', 'class_room_id'])
            ->each(function (CalendarDay $day) use (&$days) {
                $days[$day->date->toDateString()][] = (int) $day->class_room_id;
            });

        return $this->classCache[$year] = $days;
    }

    /**
     * Tanggal Libur Total (Tahfizh & Adab) setahun -- bentuk lama Setting::getNationalHolidays().
     *
     * @return array<int, string>
     */
    public function totalHolidays(int $year): array
    {
        return array_keys(array_filter(
            $this->globalDays($year),
            fn (array $day) => $day['tahfizh_off'] && $day['adab_off']
        ));
    }

    public function isTahfizhHoliday(?ClassRoom $classRoom, CarbonInterface $date): bool
    {
        $dateString = $date->toDateString();

        if ($this->globalDays($date->year)[$dateString]['tahfizh_off'] ?? false) {
            return true;
        }

        return $classRoom !== null
            && in_array($classRoom->id, $this->classDays($date->year)[$dateString] ?? [], true);
    }

    /**
     * Hari pertemuan Tahfizh kelas pada pekan yang memuat tanggal ini (ISO 1-7).
     *
     * @return array<int, int>
     */
    public function classMeetingDays(ClassRoom $classRoom, CarbonInterface $date): array
    {
        $row = $this->weekSchedules($classRoom->id)[$this->weekStart($date)->toDateString()] ?? null;

        return $row ? array_map('intval', $row->days) : $classRoom->tahfizh_days;
    }

    public function weekStart(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date->toDateString())->startOfWeek(Carbon::MONDAY);
    }

    /**
     * Jadwal pekanan tersimpan satu kelas: 'Y-m-d' (Senin) => ClassWeekSchedule.
     *
     * @return array<string, ClassWeekSchedule>
     */
    public function weekSchedules(int $classRoomId): array
    {
        return $this->weekCache[$classRoomId] ??= ClassWeekSchedule::query()
            ->where('class_room_id', $classRoomId)
            ->get()
            ->keyBy(fn (ClassWeekSchedule $row) => $row->week_start->toDateString())
            ->all();
    }

    /**
     * Keadaan jadwal satu kelas di satu pekan.
     *
     * @return array{days: array<int, int>, is_custom: bool, locked: bool, auto_locked: bool, manually_locked: bool, unlocked: bool}
     */
    public function weekState(ClassRoom $classRoom, CarbonInterface $weekStart): array
    {
        $weekStart = $this->weekStart($weekStart);
        $row = $this->weekSchedules($classRoom->id)[$weekStart->toDateString()] ?? null;
        $ended = $this->weekEnded($weekStart);
        $unlocked = (bool) $row?->unlocked;

        return [
            'days' => $row ? array_map('intval', $row->days) : $classRoom->tahfizh_days,
            'is_custom' => (bool) $row?->is_custom,
            'locked' => ! $unlocked && ($ended || $row?->locked_at !== null),
            'auto_locked' => $ended && ! $unlocked && $row?->locked_at === null,
            'manually_locked' => ! $unlocked && $row?->locked_at !== null,
            'unlocked' => $unlocked,
        ];
    }

    public function weekEnded(CarbonInterface $weekStart): bool
    {
        return $this->weekStart($weekStart)->addDays(6)->endOfDay()->lt(now());
    }

    /**
     * Simpan jadwal khusus satu kelas di satu pekan. Pekan terkunci ditolak (return false).
     *
     * @param  array<int, int|string>  $days
     */
    public function saveWeek(ClassRoom $classRoom, CarbonInterface $weekStart, array $days, ?int $userId = null): bool
    {
        $weekStart = $this->weekStart($weekStart);
        $state = $this->weekState($classRoom, $weekStart);
        if ($state['locked']) {
            return false;
        }

        $days = $this->normalizeDays($days);
        $isDefault = $days === $this->normalizeDays($classRoom->tahfizh_days);
        $row = $this->weekSchedules($classRoom->id)[$weekStart->toDateString()] ?? null;

        if ($row === null) {
            if (! $isDefault) {
                ClassWeekSchedule::create([
                    'class_room_id' => $classRoom->id, 'week_start' => $weekStart->toDateString(),
                    'days' => $days, 'is_custom' => true,
                ]);
            }
        } elseif ($isDefault && ! $row->unlocked && $row->locked_at === null) {
            $row->delete();
        } elseif ($row->days !== $days || $row->is_custom === $isDefault) {
            $row->update(['days' => $days, 'is_custom' => ! $isDefault]);
        }

        return true;
    }

    public function lockWeek(ClassRoom $classRoom, CarbonInterface $weekStart, ?int $userId = null): void
    {
        $weekStart = $this->weekStart($weekStart);
        ClassWeekSchedule::updateOrCreate(
            ['class_room_id' => $classRoom->id, 'week_start' => $weekStart->toDateString()],
            [
                'days' => $this->weekState($classRoom, $weekStart)['days'],
                'locked_at' => now(), 'locked_by' => $userId, 'unlocked' => false,
            ]
        );
    }

    public function unlockWeek(ClassRoom $classRoom, CarbonInterface $weekStart): void
    {
        $weekStart = $this->weekStart($weekStart);
        ClassWeekSchedule::updateOrCreate(
            ['class_room_id' => $classRoom->id, 'week_start' => $weekStart->toDateString()],
            [
                // Salin jadwal yang berlaku sekarang supaya membuka kunci tidak mengubah apa pun.
                'days' => $this->weekState($classRoom, $weekStart)['days'],
                'locked_at' => null, 'locked_by' => null, 'unlocked' => true,
            ]
        );
    }

    /**
     * Sebelum jadwal default kelas berubah: bekukan pekan-pekan yang sudah lewat (tanpa
     * jadwal tersimpan) dengan jadwal lama, supaya TM/jurnal/target lampau tidak ikut berubah.
     * Mencakup tahun ajaran berjalan & sebelumnya, tidak sebelum kelas dibuat.
     *
     * @param  array<int, int>  $previousDays
     */
    public function snapshotPastWeeks(ClassRoom $classRoom, array $previousDays): void
    {
        $today = now();
        $academicStartYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $from = $this->weekStart(Carbon::create($academicStartYear - 1, 7, 1));
        if ($classRoom->created_at && $classRoom->created_at->gt($from)) {
            $from = $this->weekStart($classRoom->created_at);
        }

        $existing = array_keys($this->weekSchedules($classRoom->id));
        $days = json_encode($this->normalizeDays($previousDays));
        $rows = [];

        for ($week = $from->copy(); $this->weekEnded($week); $week->addWeek()) {
            if (! in_array($week->toDateString(), $existing, true)) {
                $rows[] = [
                    'class_room_id' => $classRoom->id, 'week_start' => $week->toDateString(), 'days' => $days,
                    'is_custom' => false, 'unlocked' => false, 'created_at' => $today, 'updated_at' => $today,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ClassWeekSchedule::insert($chunk);
        }
        unset($this->weekCache[$classRoom->id]);
    }

    /**
     * @param  array<int, int|string>  $days
     * @return array<int, int>
     */
    private function normalizeDays(array $days): array
    {
        $days = array_values(array_unique(array_filter(array_map('intval', $days), fn ($d) => $d >= 1 && $d <= 7)));
        sort($days);

        return $days;
    }

    public function isTahfizhEffectiveDay(ClassRoom $classRoom, CarbonInterface $date, array $onlyDays = []): bool
    {
        $allowedDays = $this->classMeetingDays($classRoom, $date);
        if ($onlyDays !== []) {
            $allowedDays = array_intersect($allowedDays, $onlyDays);
        }

        return in_array($date->dayOfWeekIso, $allowedDays, true)
            && ! $this->isTahfizhHoliday($classRoom, $date);
    }

    /**
     * Hari efektif Adab dalam sebulan ['Y-m-d' => true]; bulan berjalan dihitung sampai hari ini.
     *
     * @return array<string, true>
     */
    public function adabEffectiveDates(int $year, int $month, ?string $untilDate = null): array
    {
        $key = "{$year}-{$month}-".($untilDate ?? 'auto');
        if (isset($this->adabDatesCache[$key])) {
            return $this->adabDatesCache[$key];
        }

        $cursor = Carbon::create($year, $month, 1)->startOfDay();
        $end = match (true) {
            $untilDate !== null => Carbon::parse($untilDate)->endOfDay(),
            $cursor->isSameMonth(now()) => now()->endOfDay(),
            default => $cursor->copy()->endOfMonth(),
        };

        $dates = [];
        while ($cursor->lte($end) && $cursor->month === $month) {
            if ($this->isAdabEffectiveDay($cursor)) {
                $dates[$cursor->toDateString()] = true;
            }
            $cursor->addDay();
        }

        return $this->adabDatesCache[$key] = $dates;
    }

    public function isAdabEffectiveDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->adabDays(), true)
            && ! ($this->globalDays($date->year)[$date->toDateString()]['adab_off'] ?? false);
    }

    /**
     * Simpan status seluruh tanggal satu bulan (menggantikan isi bulan itu).
     *
     * @param  array<string, array{tahfizh_off: bool, adab_off: bool}>  $globalDays
     * @param  array<string, array<int, int>>  $classDays
     */
    public function saveMonth(int $year, int $month, array $globalDays, array $classDays, ?int $userId = null): void
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $inMonth = fn (string $date) => str_starts_with($date, $monthStart->format('Y-m-'));

        // Tahun pertama kali diatur: bekukan libur bawaan bulan-bulan lain supaya tidak hilang.
        if (! $this->isYearConfigured($year)) {
            foreach ($this->globalDays($year) as $date => $day) {
                if (! $inMonth($date)) {
                    CalendarDay::updateOrCreate(
                        ['date' => $date, 'class_room_id' => null],
                        ['tahfizh_off' => $day['tahfizh_off'], 'adab_off' => $day['adab_off'], 'updated_by' => $userId]
                    );
                }
            }
            Setting::set("calendar_configured_{$year}", '1');
        }

        CalendarDay::query()->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->delete();

        foreach ($globalDays as $date => $day) {
            if ($inMonth($date) && ($day['tahfizh_off'] || $day['adab_off'])) {
                CalendarDay::create([
                    'date' => $date, 'class_room_id' => null,
                    'tahfizh_off' => (bool) $day['tahfizh_off'], 'adab_off' => (bool) $day['adab_off'],
                    'updated_by' => $userId,
                ]);
            }
        }

        $validClassIds = ClassRoom::query()->pluck('id')->all();
        foreach ($classDays as $date => $classIds) {
            if (! $inMonth($date)) {
                continue;
            }
            foreach (array_unique(array_map('intval', $classIds)) as $classId) {
                if (in_array($classId, $validClassIds, true)) {
                    CalendarDay::create([
                        'date' => $date, 'class_room_id' => $classId,
                        'tahfizh_off' => true, 'adab_off' => false, 'updated_by' => $userId,
                    ]);
                }
            }
        }

        $this->flush();
    }

    /**
     * Isi kalender satu bulan (hanya tanggal yang tercatat).
     *
     * @return array{global: array<string, array{tahfizh_off: bool, adab_off: bool}>, class: array<string, array<int, int>>}
     */
    public function monthDays(int $year, int $month): array
    {
        $prefix = sprintf('%04d-%02d-', $year, $month);
        $inMonth = fn ($value, string $date) => str_starts_with($date, $prefix);

        return [
            'global' => array_filter($this->globalDays($year), $inMonth, ARRAY_FILTER_USE_BOTH),
            'class' => array_filter($this->classDays($year), $inMonth, ARRAY_FILTER_USE_BOTH),
        ];
    }

    /**
     * Simpan perubahan satu bulan, tapi cakupan yang tidak boleh diubah (hak akses atau
     * bulan terkunci) tetap memakai isi lama -- apa pun yang dikirim form.
     *
     * @param  array<string, array{tahfizh_off: bool, adab_off: bool}>  $globalDays
     * @param  array<string, array<int, int>>  $classDays
     */
    public function updateMonth(int $year, int $month, array $globalDays, array $classDays, bool $canTahfizh, bool $canAdab, ?int $userId = null): void
    {
        $canTahfizh = $canTahfizh && ! $this->isMonthLocked($year, $month, self::SCOPE_TAHFIZH);
        $canAdab = $canAdab && ! $this->isMonthLocked($year, $month, self::SCOPE_ADAB);

        if (! $canTahfizh && ! $canAdab) {
            return;
        }

        $current = $this->monthDays($year, $month);
        $merged = [];
        foreach (array_unique(array_merge(array_keys($current['global']), array_keys($globalDays))) as $date) {
            $merged[$date] = [
                'tahfizh_off' => (bool) ($canTahfizh ? ($globalDays[$date]['tahfizh_off'] ?? false) : ($current['global'][$date]['tahfizh_off'] ?? false)),
                'adab_off' => (bool) ($canAdab ? ($globalDays[$date]['adab_off'] ?? false) : ($current['global'][$date]['adab_off'] ?? false)),
            ];
        }

        $this->saveMonth($year, $month, $merged, $canTahfizh ? $classDays : $current['class'], $userId);
    }

    public function isMonthLocked(int $year, int $month, string $scope): bool
    {
        return in_array($scope, $this->monthLocks($year, $month), true);
    }

    /**
     * Cakupan yang terkunci pada bulan ini.
     *
     * @return array<int, string>
     */
    public function monthLocks(int $year, int $month): array
    {
        return $this->lockCache["{$year}-{$month}"] ??= CalendarMonthLock::query()
            ->where('year', $year)
            ->where('month', $month)
            ->pluck('scope')
            ->all();
    }

    public function lockMonth(int $year, int $month, string $scope, ?int $userId = null): void
    {
        CalendarMonthLock::firstOrCreate(['year' => $year, 'month' => $month, 'scope' => $scope], ['locked_by' => $userId]);
        unset($this->lockCache["{$year}-{$month}"]);
    }

    public function unlockMonth(int $year, int $month, string $scope): void
    {
        // Lewat model (bukan query delete) supaya tercatat di Audit Log.
        CalendarMonthLock::query()->where(['year' => $year, 'month' => $month, 'scope' => $scope])->get()->each->delete();
        unset($this->lockCache["{$year}-{$month}"]);
    }

    /**
     * Hari pengisian kuisioner Adab (ISO 1-7).
     *
     * @return array<int, int>
     */
    public function adabDays(): array
    {
        if ($this->adabDaysCache !== null) {
            return $this->adabDaysCache;
        }

        $saved = json_decode((string) Setting::get('adab_days'), true);

        return $this->adabDaysCache = is_array($saved) && $saved !== []
            ? array_values(array_map('intval', $saved))
            : self::ADAB_DAYS;
    }

    /**
     * @param  array<int, int|string>  $days
     */
    public function saveAdabDays(array $days): void
    {
        $days = array_values(array_unique(array_filter(array_map('intval', $days), fn ($d) => $d >= 1 && $d <= 7)));
        sort($days);
        Setting::set('adab_days', json_encode($days));
        $this->flush();
    }

    public function flush(): void
    {
        $this->globalCache = [];
        $this->classCache = [];
        $this->adabDatesCache = [];
        $this->lockCache = [];
        $this->weekCache = [];
        $this->adabDaysCache = null;
        Setting::flushCalendarCaches();
    }

    private function isYearConfigured(int $year): bool
    {
        return Setting::get("calendar_configured_{$year}") === '1';
    }
}
