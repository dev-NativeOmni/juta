<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $holidaysCache = [];

    protected static array $effectiveDatesSetCache = [];

    protected static array $effectiveDaysCountCache = [];

    protected static array $studentAdabScoreCache = [];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
        self::$holidaysCache = [];
        self::$effectiveDatesSetCache = [];
        self::$effectiveDaysCountCache = [];
        self::$studentAdabScoreCache = [];

        return $setting;
    }

    /**
     * Returns the 4 adab categories with their questions.
     * Each category has a 'title', 'desc', and 'questions' (array of strings).
     */
    public static function getAdabQuestions(): array
    {
        $default = [
            [
                'title' => '🕋 Adab Kepada Allah',
                'desc' => 'Menjaga hubungan ketakwaan dan ibadah sehari-hari kepada Allah Subhanahu wa Ta\'ala.',
                'questions' => [
                    'Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini?',
                    'Apakah Anda mengawali aktivitas hari ini dengan membaca Basmalah?',
                    'Apakah Anda selalu berdoa setelah selesai shalat fardhu hari ini?',
                    'Apakah Anda bersyukur atas segala nikmat yang Anda rasakan hari ini?',
                    'Apakah Anda menyempatkan diri berdzikir (membaca tasbih/tahmid/takbir) hari ini?',
                ],
            ],
            [
                'title' => '👥 Adab Kepada Sesama Teman',
                'desc' => 'Menjalin hubungan yang baik, saling menghormati, dan berlaku adil terhadap sesama.',
                'questions' => [
                    'Apakah Anda bersikap sopan dan santun kepada teman-teman hari ini?',
                    'Apakah Anda menghindari perkataan kasar, mengejek, atau menyakiti teman?',
                    'Apakah Anda membantu teman yang membutuhkan pertolongan hari ini?',
                    'Apakah Anda menjaga amanah dan kejujuran dalam pergaulan hari ini?',
                    'Apakah Anda ikut menjaga kerukunan dan ketenangan di lingkungan asrama/kelas?',
                ],
            ],
            [
                'title' => '📚 Adab Ketika Belajar',
                'desc' => 'Menjaga ketertiban, kebersihan, kepatuhan, dan doa dalam menuntut ilmu.',
                'questions' => [
                    'Apakah Anda datang/masuk kelas tepat waktu dan menyiapkan peralatan belajar?',
                    'Apakah Anda menyimak penjelasan guru dengan khusyuk dan tidak mengobrol saat pelajaran?',
                    'Apakah Anda mencatat materi pelajaran dengan rapi dan tertib?',
                    'Apakah Anda mengawali dan mengakhiri belajar dengan berdoa?',
                    'Apakah Anda menjaga kebersihan dan kerapian tempat belajar Anda?',
                ],
            ],
            [
                'title' => '🌿 Adab terhadap Lingkungan',
                'desc' => 'Menjaga kebersihan, ketertiban, dan kelestarian lingkungan sebagai bentuk syukur kepada Allah.',
                'questions' => [
                    'Apakah Anda membuang sampah pada tempatnya hari ini?',
                    'Apakah Anda menjaga kebersihan kamar/asrama Anda hari ini?',
                    'Apakah Anda turut merawat fasilitas sekolah/pesantren dengan baik?',
                    'Apakah Anda bersikap hemat dalam menggunakan air, listrik, atau barang fasilitas?',
                    'Apakah Anda tidak merusak atau mencoret-coret benda/properti milik bersama?',
                ],
            ],
        ];

        $json = self::get('adab_questions');
        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && count($decoded) >= 1) {
                return $decoded;
            }
        }

        return $default;
    }

    /**
     * Get list of national holidays for a given year.
     * Checks database setting 'national_holidays_{year}' first, then falls back to defaults.
     */
    public static function getNationalHolidays(int $year): array
    {
        if (isset(self::$holidaysCache[$year])) {
            return self::$holidaysCache[$year];
        }

        $custom = self::get("national_holidays_{$year}");
        if ($custom) {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                return self::$holidaysCache[$year] = $decoded;
            }
        }

        // Default Indonesian national holidays (fixed dates + common movable holidays estimate)
        return self::$holidaysCache[$year] = [
            "{$year}-01-01", // Tahun Baru Masehi
            "{$year}-05-01", // Hari Buruh
            "{$year}-06-01", // Hari Lahir Pancasila
            "{$year}-08-17", // Hari Kemerdekaan RI
            "{$year}-12-25", // Hari Natal
        ];
    }

    /**
     * Check if a date is an effective day for Adab questionnaire (Selasa-Jumat, excluding national holidays).
     */
    public static function isEffectiveAdabDay(Carbon $date, array $holidays = []): bool
    {
        // ISO day of week: 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat, 6=Sabtu, 7=Minggu
        $dayIso = $date->dayOfWeekIso;
        $isTuesdayToFriday = ($dayIso >= 2 && $dayIso <= 5);

        return $isTuesdayToFriday && ! in_array($date->toDateString(), $holidays, true);
    }

    /**
     * Get associative set of effective dates for a given month ['YYYY-MM-DD' => true] for O(1) lookup.
     */
    public static function getEffectiveDatesSet(int $year, int $month, ?string $untilDate = null): array
    {
        $cacheKey = "{$year}_{$month}_".($untilDate ?? 'full');
        if (isset(self::$effectiveDatesSetCache[$cacheKey])) {
            return self::$effectiveDatesSetCache[$cacheKey];
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $startDate->daysInMonth;

        $now = Carbon::now();
        $isCurrentMonth = ($year === (int) $now->format('Y') && $month === (int) $now->format('n'));

        if ($untilDate) {
            $endDate = Carbon::parse($untilDate)->endOfDay();
        } elseif ($isCurrentMonth) {
            $endDate = $now->copy()->endOfDay();
        } else {
            $endDate = Carbon::createFromDate($year, $month, $daysInMonth)->endOfDay();
        }

        $holidays = self::getNationalHolidays($year);
        $set = [];

        $current = $startDate->copy();
        while ($current->lte($endDate) && $current->month === $month) {
            if (self::isEffectiveAdabDay($current, $holidays)) {
                $set[$current->toDateString()] = true;
            }
            $current->addDay();
        }

        return self::$effectiveDatesSetCache[$cacheKey] = $set;
    }

    /**
     * Calculate count of effective workdays (Selasa-Jumat, excluding national holidays) for a month.
     */
    public static function getEffectiveDaysCount(int $year, int $month, ?string $untilDate = null): int
    {
        $cacheKey = "{$year}_{$month}_".($untilDate ?? 'full');
        if (isset(self::$effectiveDaysCountCache[$cacheKey])) {
            return self::$effectiveDaysCountCache[$cacheKey];
        }

        $datesSet = self::getEffectiveDatesSet($year, $month, $untilDate);

        return self::$effectiveDaysCountCache[$cacheKey] = max(1, count($datesSet));
    }

    /**
     * Get student adab questionnaire attendance details for a month.
     */
    public static function getStudentAdabAttendanceDetails(int $studentId, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $effectiveDaysTotal = self::getEffectiveDaysCount($year, $month);
        $effectiveDatesSet = self::getEffectiveDatesSet($year, $month);

        // Fetch distinct assessment dates filled by student in effective days
        $filledDates = AdabRecord::where('student_id', $studentId)
            ->whereBetween('assessment_date', [$startDate, $endDate])
            ->pluck('assessment_date')
            ->unique();

        $effectiveDaysFilled = 0;
        foreach ($filledDates as $dateStr) {
            $d = is_string($dateStr) ? substr($dateStr, 0, 10) : (is_object($dateStr) ? $dateStr->format('Y-m-d') : '');
            if (isset($effectiveDatesSet[$d])) {
                $effectiveDaysFilled++;
            }
        }

        $attendanceRate = round(($effectiveDaysFilled / $effectiveDaysTotal) * 100, 1);

        return [
            'effective_days_total' => $effectiveDaysTotal,
            'effective_days_filled' => $effectiveDaysFilled,
            'attendance_rate' => min(100.0, $attendanceRate),
        ];
    }

    /**
     * Calculate composite adab score: 40% questionnaire attendance + 60% mentor score.
     */
    public static function calculateAdabScore(int $studentId, int $year, int $month): array
    {
        $cacheKey = "{$studentId}_{$year}_{$month}";
        if (isset(self::$studentAdabScoreCache[$cacheKey])) {
            return self::$studentAdabScoreCache[$cacheKey];
        }

        $attendance = self::getStudentAdabAttendanceDetails($studentId, $year, $month);
        $attendanceRate = $attendance['attendance_rate'];

        $mentorAssessment = AdabMentorAssessment::where('student_id', $studentId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $mentorAssessment) {
            // Fallback: try latest available mentor assessment or use attendance rate
            $mentorAssessment = AdabMentorAssessment::where('student_id', $studentId)
                ->orderByDesc('year')->orderByDesc('month')
                ->first();
        }

        $mentorScore = $mentorAssessment ? (float) $mentorAssessment->mentor_score : null;

        if ($mentorScore !== null) {
            $finalScore = round(($attendanceRate * 0.40) + ($mentorScore * 0.60), 1);
        } else {
            $finalScore = $attendanceRate;
        }

        $grade = self::getAdabGrade($finalScore);
        $gradeLabel = self::getAdabGradeLabel($grade);

        $result = [
            'attendance_rate' => $attendanceRate,
            'effective_days_filled' => $attendance['effective_days_filled'],
            'effective_days_total' => $attendance['effective_days_total'],
            'mentor_score' => $mentorScore,
            'final_score' => $finalScore,
            'grade' => $grade,
            'grade_label' => $gradeLabel,
        ];

        return self::$studentAdabScoreCache[$cacheKey] = $result;
    }

    /**
     * Convert a 0-100 percentage score to a letter grade.
     */
    public static function getAdabGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Get grade label in Bahasa Indonesia.
     */
    public static function getAdabGradeLabel(string $grade): string
    {
        return match ($grade) {
            'A' => 'Mumtaz (Sangat Baik)',
            'B' => 'Jayyid Jiddan (Baik Sekali)',
            'C' => 'Jayyid (Baik)',
            'D' => 'Maqbul (Cukup)',
            default => 'Dha\'if (Kurang)',
        };
    }

    /**
     * Get hafalan targets configuration per grade level and program.
     */
    public static function getHafalanTargetsConfig(): array
    {
        $default = [
            'grade_10' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'specific',
                    'specific_juz' => [30, 29, 28, 1],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'specific',
                    'specific_juz' => [30, 29],
                ],
            ],
            'grade_11' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
            ],
            'grade_12' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
            ],
        ];

        $val = self::get('hafalan_targets_config');
        if (! $val) {
            return $default;
        }

        $decoded = is_string($val) ? json_decode($val, true) : $val;

        return is_array($decoded) ? array_replace_recursive($default, $decoded) : $default;
    }
}
