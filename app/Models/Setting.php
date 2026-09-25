<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

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
        self::$studentAdabScoreCache = [];

        return $setting;
    }

    /**
     * Dipanggil SchoolCalendar saat kalender berubah: skor adab bergantung pada hari efektif.
     */
    public static function flushCalendarCaches(): void
    {
        self::$studentAdabScoreCache = [];
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
     * Tanggal Libur Total (Tahfizh & Adab) setahun. Lihat App\Services\SchoolCalendar.
     */
    public static function getNationalHolidays(int $year): array
    {
        return app(SchoolCalendar::class)->totalHolidays($year);
    }

    /**
     * Libur Tahfizh khusus kelas ('Y-m-d' => [class_room_id, ...]). Lihat App\Services\SchoolCalendar.
     */
    public static function getClassHolidays(int $year): array
    {
        return app(SchoolCalendar::class)->classDays($year);
    }

    /**
     * Hari efektif kuisioner Adab: hari pengisian Adab (Kalender), bukan libur Adab (lihat SchoolCalendar).
     */
    public static function isEffectiveAdabDay(Carbon $date): bool
    {
        return app(SchoolCalendar::class)->isAdabEffectiveDay($date);
    }

    /**
     * Get associative set of effective dates for a given month ['YYYY-MM-DD' => true] for O(1) lookup.
     */
    public static function getEffectiveDatesSet(int $year, int $month, ?string $untilDate = null): array
    {
        return app(SchoolCalendar::class)->adabEffectiveDates($year, $month, $untilDate);
    }

    /**
     * Jumlah hari efektif kuisioner Adab dalam sebulan (lihat SchoolCalendar::isAdabEffectiveDay).
     */
    public static function getEffectiveDaysCount(int $year, int $month, ?string $untilDate = null): int
    {
        return max(1, count(self::getEffectiveDatesSet($year, $month, $untilDate)));
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
     * Batch variant of calculateAdabScore() for many students at once.
     *
     * @param  array<int>  $studentIds
     * @return array<int, array> keyed by student_id, same shape as calculateAdabScore()
     */
    public static function calculateAdabScoresForStudents(array $studentIds, int $year, int $month): array
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));

        if ($studentIds === []) {
            return [];
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $effectiveDaysTotal = self::getEffectiveDaysCount($year, $month);
        $effectiveDatesSet = self::getEffectiveDatesSet($year, $month);

        $filledDatesByStudent = AdabRecord::whereIn('student_id', $studentIds)
            ->whereBetween('assessment_date', [$startDate, $endDate])
            ->get(['student_id', 'assessment_date'])
            ->groupBy('student_id');

        $currentMonthAssessments = AdabMentorAssessment::whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->where('month', $month)
            ->get(['student_id', 'mentor_score'])
            ->keyBy(fn ($assessment) => (int) $assessment->student_id);

        $missingIds = array_values(array_diff(
            $studentIds,
            $currentMonthAssessments->keys()->all()
        ));

        $latestFallbackByStudent = [];

        if ($missingIds !== []) {
            AdabMentorAssessment::whereIn('student_id', $missingIds)
                ->get(['student_id', 'mentor_score', 'year', 'month'])
                ->groupBy(fn ($assessment) => (int) $assessment->student_id)
                ->each(function ($assessments, $studentId) use (&$latestFallbackByStudent) {
                    $latestFallbackByStudent[$studentId] = $assessments
                        ->sortByDesc(fn ($a) => sprintf('%04d%02d', $a->year, $a->month))
                        ->first();
                });
        }

        $results = [];

        foreach ($studentIds as $studentId) {
            $cacheKey = "{$studentId}_{$year}_{$month}";

            if (isset(self::$studentAdabScoreCache[$cacheKey])) {
                $results[$studentId] = self::$studentAdabScoreCache[$cacheKey];

                continue;
            }

            $filledDates = ($filledDatesByStudent->get($studentId) ?? collect())
                ->pluck('assessment_date')
                ->unique();

            $effectiveDaysFilled = 0;
            foreach ($filledDates as $dateStr) {
                $d = is_string($dateStr) ? substr($dateStr, 0, 10) : (is_object($dateStr) ? $dateStr->format('Y-m-d') : '');
                if (isset($effectiveDatesSet[$d])) {
                    $effectiveDaysFilled++;
                }
            }

            $attendanceRate = min(100.0, round(($effectiveDaysFilled / $effectiveDaysTotal) * 100, 1));

            $mentorAssessment = $currentMonthAssessments->get($studentId) ?? ($latestFallbackByStudent[$studentId] ?? null);
            $mentorScore = $mentorAssessment ? (float) $mentorAssessment->mentor_score : null;

            $finalScore = $mentorScore !== null
                ? round(($attendanceRate * 0.40) + ($mentorScore * 0.60), 1)
                : $attendanceRate;

            $grade = self::getAdabGrade($finalScore);

            $result = [
                'attendance_rate' => $attendanceRate,
                'effective_days_filled' => $effectiveDaysFilled,
                'effective_days_total' => $effectiveDaysTotal,
                'mentor_score' => $mentorScore,
                'final_score' => $finalScore,
                'grade' => $grade,
                'grade_label' => self::getAdabGradeLabel($grade),
            ];

            self::$studentAdabScoreCache[$cacheKey] = $result;
            $results[$studentId] = $result;
        }

        return $results;
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

    /**
     * Get tahfizh final-grade scoring configuration: how much of the
     * combined score (max 100) comes from target completion vs the exam,
     * and how many points an incomplete target still earns.
     */
    public static function getTahfizhScoringConfig(): array
    {
        $default = [
            'target_weight' => 50,
            'exam_weight' => 50,
            'target_incomplete_score' => 40,
        ];

        $val = self::get('tahfizh_scoring_config');
        if (! $val) {
            return $default;
        }

        $decoded = is_string($val) ? json_decode($val, true) : $val;

        return is_array($decoded) ? array_replace_recursive($default, $decoded) : $default;
    }

    /**
     * Combine a student's latest target-completion status with their
     * latest exam score into the final tahfizh grade (max 100) shown on
     * the report card.
     */
    public static function calculateTahfizhScore(Student $student): array
    {
        $config = self::getTahfizhScoringConfig();

        $latestTarget = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereNotNull('surah_id')
            ->orderByDesc('target_date')
            ->orderByDesc('id')
            ->first();

        $targetScore = null;
        if ($latestTarget) {
            $targetScore = $latestTarget->status === 'completed'
                ? $config['target_weight']
                : $config['target_incomplete_score'];
            $targetScore = min($targetScore, $config['target_weight']);
        }

        $latestExam = TahfizhExam::query()
            ->where('student_id', $student->id)
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->first();

        $examScore = null;
        if ($latestExam) {
            // Defensive cap: exams recorded before the scoring simplification
            // may still hold a legacy 0-100 average-of-5 value.
            $examScore = min((float) $latestExam->total_score, $config['exam_weight']);
        }

        return [
            'target_score' => $targetScore,
            'target_weight' => $config['target_weight'],
            'target_status' => $latestTarget?->status,
            'target_label' => $latestTarget ? ($latestTarget->status === 'completed' ? 'Tuntas' : 'Belum Tuntas') : null,
            'exam_score' => $examScore,
            'exam_weight' => $config['exam_weight'],
            'exam_date' => $latestExam?->exam_date,
            'has_target' => (bool) $latestTarget,
            'has_exam' => (bool) $latestExam,
            'final_score' => round(($targetScore ?? 0) + ($examScore ?? 0), 1),
        ];
    }
}
