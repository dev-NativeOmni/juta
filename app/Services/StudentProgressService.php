<?php

namespace App\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentProgressService
{
    private static ?array $allAyahs = null;

    private static ?array $juzTotalAyahs = null;

    public function visibleStudentQuery(?User $user): Builder
    {
        $query = Student::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin', 'headmaster', 'supervisor', 'coordinator_tahfizh', 'tanse'])) {
            return $query;
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherProfile = $user->teacherProfile
                ?? TeacherProfile::query()->where('user_id', $user->id)->first();

            if (! $teacherProfile) {
                // Smart fallback: Find teacher profile with matching name or unlinked user_id
                $teacherProfile = TeacherProfile::query()
                    ->whereHas('user', function ($q) use ($user) {
                        $q->where('name', 'like', '%'.$user->name.'%')
                          ->orWhere('username', $user->username);
                    })
                    ->first();

                if (! $teacherProfile) {
                    $teacherProfile = TeacherProfile::query()->whereNull('user_id')->first();
                }

                if ($teacherProfile && ! $teacherProfile->user_id) {
                    $teacherProfile->update(['user_id' => $user->id]);
                }
            }

            $teacherId = $teacherProfile?->id;

            if (! $teacherId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('teacher_id', $teacherId);
        }

        if ($this->userHasAnyRole($user, ['parent'])) {
            $parentId = $user->parentProfile?->id
                ?? ParentProfile::query()->where('user_id', $user->id)->value('id');

            if (! $parentId) {
                return $query->whereRaw('1 = 0');
            }

            // Relasi parent-student via pivot table parent_student (confirmed dari migration)
            return $query->whereIn('id', function ($subQuery) use ($parentId) {
                $subQuery->select('student_id')
                    ->from('parent_student')
                    ->where('parent_id', $parentId);
            });
        }

        if ($this->userHasAnyRole($user, ['student'])) {
            // students.user_id selalu ada (confirmed dari migration)
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function buildRows(Collection $students): Collection
    {
        return $students
            ->map(fn (Student $student) => $this->calculate($student))
            ->values();
    }

    public function calculate(Student $student): array
    {
        if (app()->environment('testing')) {
            return $this->computeStudentProgress($student);
        }

        return Cache::remember("student_target_prog_v6_{$student->id}", 10, function () use ($student) {
            return $this->computeStudentProgress($student);
        });
    }

    public static array $juzAyahCounts = [
        1 => 148, 2 => 111, 3 => 126, 4 => 131, 5 => 124,
        6 => 110, 7 => 149, 8 => 142, 9 => 159, 10 => 127,
        11 => 151, 12 => 170, 13 => 154, 14 => 227, 15 => 185,
        16 => 269, 17 => 190, 18 => 202, 19 => 339, 20 => 171,
        21 => 178, 22 => 169, 23 => 357, 24 => 175, 25 => 246,
        26 => 195, 27 => 399, 28 => 137, 29 => 431, 30 => 564,
    ];

    private function computeStudentProgress(Student $student): array
    {
        try {
            $totalQuranAyahs = $this->totalQuranAyahs();
            $memorizedAyahs = $this->memorizedAyahCount($student);

            $hafalanRecordsQuery = HafalanRecord::query()
                ->where('student_id', $student->id);

            $murajaahRecordsQuery = MurajaahRecord::query()
                ->where('student_id', $student->id);

            $targetQuery = HafalanTarget::query()
                ->where('student_id', $student->id);

            $classRoomName = $student->classRoom?->name ?? '';
            $classRoomLevel = $student->classRoom?->level ?? '';
            $tahfizhLevel = $student->tahfizh_level ?? 'reguler';

            $isGrade10 = (bool) (
                (preg_match('/\bX\b/i', $classRoomName) && !preg_match('/\b(XI|XII)\b/i', $classRoomName))
                || preg_match('/\b10\b/i', $classRoomName)
                || preg_match('/^X[-_\s]?E/i', $classRoomName)
                || preg_match('/kelas\s*(X|10)/i', $classRoomName)
                || (preg_match('/\bX\b/i', $classRoomLevel) && !preg_match('/\b(XI|XII)\b/i', $classRoomLevel))
                || preg_match('/\b10\b/i', $classRoomLevel)
            ) && !preg_match('/\b(XI|XII|11|12)\b/i', $classRoomName);

            $isUmmiProgram = $isGrade10 || $tahfizhLevel === 'ummi';
            $programCategory = $isUmmiProgram ? 'ummi' : 'reguler';

            $latestHafalan = (clone $hafalanRecordsQuery)
                ->with('surah')
                ->latest('submitted_at')
                ->latest()
                ->first();

            if ($isUmmiProgram) {
                $passedJuz30 = (clone $hafalanRecordsQuery)
                    ->with('surah')
                    ->where('status', 'passed')
                    ->whereHas('surah', fn ($sq) => $sq->whereBetween('number', [78, 114]))
                    ->get()
                    ->sortBy(fn ($r) => $r->surah?->number ?? 114)
                    ->first();

                if ($passedJuz30) {
                    $latestHafalan = $passedJuz30;
                }
            }

            $latestMurajaah = (clone $murajaahRecordsQuery)
                ->with('surah')
                ->latest('reviewed_at')
                ->latest()
                ->first();

            $activeTargetStatuses = ['active', 'planned', 'in_progress'];
            $juzStats = $this->getJuzStats($student);

            // ─── Determine Grade & Program Key for Target Settings ───
            if ($isGrade10) {
                $gradeKey = 'grade_10';
            } elseif (
                preg_match('/\b(XI|11)\b/i', $classRoomName)
                || preg_match('/\b(XI|11)\b/i', $classRoomLevel)
            ) {
                $gradeKey = 'grade_11';
            } elseif (
                preg_match('/\b(XII|12)\b/i', $classRoomName)
                || preg_match('/\b(XII|12)\b/i', $classRoomLevel)
            ) {
                $gradeKey = 'grade_12';
            } else {
                $gradeKey = 'grade_10';
            }

            $programName = strtolower($student->classRoom?->program?->name ?? '');
            $meetingFrequency = $student->classRoom?->program?->meeting_frequency ?? 'setiap hari';

            $isTahfizhProgram = str_contains($programName, 'tahfizh')
                || str_contains(strtolower($tahfizhLevel), 'tahfizh')
                || str_contains(strtolower($tahfizhLevel), 'akselerasi')
                || (bool) preg_match('/F1\b/i', $classRoomName);

            $programKey = $isTahfizhProgram ? 'tahfizh' : 'reguler';

            // Load configured target settings from Setting model (with robust default fallback)
            $allTargetConfigs = \App\Models\Setting::getHafalanTargetsConfig();
            $targetCfg = $allTargetConfigs[$gradeKey][$programKey] ?? [
                'target_juz_count' => $isTahfizhProgram ? 4 : 2,
                'mode' => $gradeKey === 'grade_10' ? 'specific' : 'any',
                'specific_juz' => $gradeKey === 'grade_10' ? ($isTahfizhProgram ? [30, 29, 28, 1] : [30, 29]) : [],
            ];

            $targetJuzCount = (int) ($targetCfg['target_juz_count'] ?? ($isTahfizhProgram ? 4 : 2));
            $targetMode = $targetCfg['mode'] ?? ($gradeKey === 'grade_10' ? 'specific' : 'any');
            $targetSpecificJuz = array_values(array_map('intval', (array) ($targetCfg['specific_juz'] ?? [])));

            if ($targetMode === 'specific' && !empty($targetSpecificJuz)) {
                $targetTotalAyahs = 0;
                $memorizedTargetAyahs = 0;
                foreach ($targetSpecificJuz as $j) {
                    $targetTotalAyahs += self::$juzAyahCounts[$j] ?? ($juzStats['juz_total_ayahs'][$j] ?? 208);
                    $memorizedTargetAyahs += ($juzStats['juz_memorized_count'][$j] ?? 0);
                }
                $targetJuzLabel = "{$targetJuzCount} Juz (Juz " . implode(', ', $targetSpecificJuz) . ")";
            } else {
                $targetTotalAyahs = (int) round(($targetJuzCount / 30) * $totalQuranAyahs);
                $memorizedTargetAyahs = $memorizedAyahs;
                $targetJuzLabel = "{$targetJuzCount} Juz Bebas";
            }

            $progressPercent = $targetTotalAyahs > 0
                ? round(min(100.0, ($memorizedTargetAyahs / $targetTotalAyahs) * 100), 2)
                : 0;
            $remainingAyahs = max(0, $targetTotalAyahs - $memorizedTargetAyahs);

            // ─── Ummi Program Details ───
            $latestUmmiRecord = \App\Models\UmmiRecord::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->latest('tanggal')
                ->latest()
                ->first();

            $latestUmmiTarget = HafalanTarget::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->whereNotNull('ummi_jilid')
                ->latest('target_date')
                ->first();

            if (! $latestUmmiTarget) {
                $latestUmmiTarget = HafalanTarget::query()
                    ->with('surah')
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->latest('target_date')
                    ->first();
            }

            $currentJilidStr = $latestUmmiRecord?->ummi_jilid ?? 'Jilid 1';
            preg_match('/(\d+)/', $currentJilidStr, $mJilid);
            $currentJilidNum = isset($mJilid[1]) ? min(3, max(1, (int) $mJilid[1])) : 1;
            preg_match('/(\d+)/', (string) ($latestUmmiRecord?->ummi_halaman ?? ''), $mHal);
            $currentHalaman = isset($mHal[1]) ? min(40, max(1, (int) $mHal[1])) : 1;
            $totalPagesCompleted = max(1, ($currentJilidNum - 1) * 40 + $currentHalaman);
            $ummiJilidPercent = min(100.0, round(($totalPagesCompleted / 120) * 100, 1));

            // ─── Reguler Program Details (For Grade 11 & 12) ───
            $levelBaris = match ($tahfizhLevel) {
                'tahsin' => 3,
                'reguler' => 5,
                'akselerasi' => 7,
                default => 5,
            };

            $isWeeklyProgram = ($meetingFrequency === 'seminggu sekali')
                || str_contains($programName, 'reguler')
                || (bool) preg_match('/F[2-9]\b/i', $classRoomName);

            if ($isTahfizhProgram) {
                $isWeeklyProgram = false;
            }

            $meetingsPerMonth = $isWeeklyProgram ? 4 : 20;
            $targetBarisMonth = $levelBaris * $meetingsPerMonth;

            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();

            $passedRecordsThisMonth = HafalanRecord::with('surah')
                ->where('student_id', $student->id)
                ->where('status', 'passed')
                ->whereBetween('submitted_at', [$startOfMonth, $endOfMonth])
                ->get();

            $capaianBarisMonth = $passedRecordsThisMonth->sum(fn ($r) => $r->lines_count);
            $regulerBarisPercent = $targetBarisMonth > 0 ? min(100.0, round(($capaianBarisMonth / $targetBarisMonth) * 100, 1)) : 0;

            // ─── Status Badge Calculation ───
            $overdueCount = (clone $targetQuery)
                ->whereIn('status', $activeTargetStatuses)
                ->whereDate('target_date', '<', today())
                ->count();

            $achievementPercent = $isUmmiProgram ? $ummiJilidPercent : $regulerBarisPercent;

            if ($overdueCount === 0 && $achievementPercent >= 90) {
                $statusBadge = 'tuntas';
                $statusLabel = 'Tuntas / Sesuai Target';
                $statusColor = 'emerald';
                $statusIcon = '🟢';
            } elseif ($overdueCount === 0 && $achievementPercent >= 70) {
                $statusBadge = 'mendekati';
                $statusLabel = 'Mendekati Target';
                $statusColor = 'amber';
                $statusIcon = '🟡';
            } else {
                $statusBadge = 'perlu_perhatian';
                $statusLabel = 'Perlu Ditingkatkan';
                $statusColor = 'rose';
                $statusIcon = '🔴';
            }

            return [
                'student' => $student,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_number' => $student->student_number ?? null,
                'class_room_name' => $student->classRoom?->name,
                'program_name' => $student->classRoom?->program?->name,
                'program_category' => $programCategory,
                'is_ummi_program' => $isUmmiProgram,
                'tahfizh_level' => $tahfizhLevel,

                'status_badge' => $statusBadge,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'status_icon' => $statusIcon,

                // Ummi Metrics
                'ummi_record' => $latestUmmiRecord,
                'ummi_target' => $latestUmmiTarget,
                'ummi_jilid_str' => $currentJilidStr,
                'ummi_jilid_num' => $currentJilidNum,
                'ummi_halaman' => $latestUmmiRecord?->ummi_halaman ?? '-',
                'ummi_jilid_percent' => $ummiJilidPercent,
                'ummi_tatap_muka' => $latestUmmiRecord?->tatap_muka ?? '-',
                'ummi_munaqasyah_score' => $latestUmmiRecord?->nilai_munaqasyah ?? null,

                // Reguler Metrics
                'level_baris' => $levelBaris,
                'capaian_baris_month' => $capaianBarisMonth,
                'target_baris_month' => $targetBarisMonth,
                'reguler_baris_percent' => $regulerBarisPercent,

                // Dynamic Program Target Metrics
                'total_quran_ayahs' => $targetTotalAyahs,
                'target_total_ayahs' => $targetTotalAyahs,
                'target_quran_ayahs' => $targetTotalAyahs,
                'target_juz_count' => $targetJuzCount,
                'target_juz_label' => $targetJuzLabel,
                'target_mode' => $targetMode,
                'target_specific_juz' => $targetSpecificJuz,
                'target_grade_key' => $gradeKey,
                'target_program_key' => $programKey,
                'target_progress_percent' => $progressPercent,
                'memorized_ayahs' => $memorizedTargetAyahs,
                'all_memorized_ayahs' => $memorizedAyahs,
                'remaining_ayahs' => $remainingAyahs,
                'progress_percent' => $progressPercent,
                'completed_juz_count' => $juzStats['juz_count'],
                'completed_juz_list' => $juzStats['juz_count'] > 0
                    ? 'Juz '.implode(', ', $juzStats['completed_juz'])
                    : 'Belum ada Juz lengkap',

                'total_hafalan_records' => (clone $hafalanRecordsQuery)->count() + \App\Models\UmmiRecord::where('student_id', $student->id)->whereNotNull('hafalan_surah_id')->count(),
                'passed_hafalan_records' => (clone $hafalanRecordsQuery)
                    ->where('status', 'passed')
                    ->count() + \App\Models\UmmiRecord::where('student_id', $student->id)->whereNotNull('hafalan_surah_id')->count(),
                'repeat_hafalan_records' => (clone $hafalanRecordsQuery)
                    ->whereIn('status', ['repeat', 'needs_improvement'])
                    ->count(),

                'total_murajaah_records' => (clone $murajaahRecordsQuery)->count(),
                'average_hafalan_score' => round((float) (clone $hafalanRecordsQuery)->avg('score'), 2),
                'average_murajaah_score' => $this->averageMurajaahScore($student),

                'total_targets' => (clone $targetQuery)->count(),
                'active_targets' => (clone $targetQuery)
                    ->whereIn('status', $activeTargetStatuses)
                    ->count(),
                'completed_targets' => (clone $targetQuery)
                    ->where('status', 'completed')
                    ->count(),
                'missed_targets' => (clone $targetQuery)
                    ->where('status', 'missed')
                    ->count(),
                'overdue_targets' => (clone $targetQuery)
                    ->whereIn('status', $activeTargetStatuses)
                    ->whereDate('target_date', '<', today())
                    ->count(),
                'latest_hafalan_surah' => ($latestUmmiRecord && $latestUmmiRecord->hafalan_surah_id && (! $latestHafalan || $latestUmmiRecord->tanggal >= ($latestHafalan->submitted_at ?? '1970-01-01')))
                    ? ($latestUmmiRecord->surah?->name_latin ?? $latestUmmiRecord->surah?->name)
                    : ($latestHafalan?->surah?->name_latin ?? $latestHafalan?->surah?->name ?? null),
                'latest_hafalan_ayah' => ($latestUmmiRecord && $latestUmmiRecord->hafalan_surah_id && (! $latestHafalan || $latestUmmiRecord->tanggal >= ($latestHafalan->submitted_at ?? '1970-01-01')))
                    ? ($latestUmmiRecord->hafalan_ayah ? 'Ayat '.$latestUmmiRecord->hafalan_ayah : null)
                    : ($latestHafalan ? $latestHafalan->ayah_start.' - '.$latestHafalan->ayah_end : null),
                'latest_hafalan_date' => ($latestUmmiRecord && $latestUmmiRecord->hafalan_surah_id && (! $latestHafalan || $latestUmmiRecord->tanggal >= ($latestHafalan->submitted_at ?? '1970-01-01')))
                    ? $latestUmmiRecord->tanggal
                    : $latestHafalan?->submitted_at,

                'latest_murajaah_surah' => $latestMurajaah?->surah?->name_latin
                    ?? $latestMurajaah?->surah?->name
                    ?? null,
                'latest_murajaah_ayah' => $latestMurajaah
                    ? $latestMurajaah->ayah_start.' - '.$latestMurajaah->ayah_end
                    : null,
                'latest_murajaah_date' => $latestMurajaah?->reviewed_at,

                'term_milestones' => $this->getTermMilestones($student),
            ];
        } catch (\Throwable $e) {
            return [
                'student' => $student,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_number' => $student->student_number ?? null,
                'class_room_name' => $student->classRoom?->name,
                'program_name' => $student->classRoom?->program?->name,
                'program_category' => 'reguler',
                'is_ummi_program' => false,
                'tahfizh_level' => $student->tahfizh_level ?? 'reguler',
                'status_badge' => 'tuntas',
                'status_label' => 'On-Track',
                'status_color' => 'emerald',
                'status_icon' => '🟢',
                'memorized_ayahs' => 0,
                'total_quran_ayahs' => 6236,
                'remaining_ayahs' => 6236,
                'progress_percent' => 0,
                'completed_juz_count' => 0,
                'completed_juz_list' => 'Belum ada Juz lengkap',
                'total_hafalan_records' => 0,
                'passed_hafalan_records' => 0,
                'repeat_hafalan_records' => 0,
                'total_murajaah_records' => 0,
                'average_hafalan_score' => 0,
                'average_murajaah_score' => 0,
                'total_targets' => 0,
                'active_targets' => 0,
                'completed_targets' => 0,
                'missed_targets' => 0,
                'overdue_targets' => 0,
                'term_milestones' => [
                    'first_record' => null,
                    'journey' => [],
                ],
            ];
        }
    }

    public static array $standardJuzRanges = [
        1 => [['surah' => 1, 'start' => 1, 'end' => 7], ['surah' => 2, 'start' => 1, 'end' => 141]],
        2 => [['surah' => 2, 'start' => 142, 'end' => 252]],
        3 => [['surah' => 2, 'start' => 253, 'end' => 286], ['surah' => 3, 'start' => 1, 'end' => 92]],
        4 => [['surah' => 3, 'start' => 93, 'end' => 200], ['surah' => 4, 'start' => 1, 'end' => 23]],
        5 => [['surah' => 4, 'start' => 24, 'end' => 147]],
        6 => [['surah' => 4, 'start' => 148, 'end' => 176], ['surah' => 5, 'start' => 1, 'end' => 81]],
        7 => [['surah' => 5, 'start' => 82, 'end' => 120], ['surah' => 6, 'start' => 1, 'end' => 110]],
        8 => [['surah' => 6, 'start' => 111, 'end' => 165], ['surah' => 7, 'start' => 1, 'end' => 87]],
        9 => [['surah' => 7, 'start' => 88, 'end' => 206], ['surah' => 8, 'start' => 1, 'end' => 40]],
        10 => [['surah' => 8, 'start' => 41, 'end' => 75], ['surah' => 9, 'start' => 1, 'end' => 92]],
        11 => [['surah' => 9, 'start' => 93, 'end' => 129], ['surah' => 10, 'start' => 1, 'end' => 109], ['surah' => 11, 'start' => 1, 'end' => 5]],
        12 => [['surah' => 11, 'start' => 6, 'end' => 123], ['surah' => 12, 'start' => 1, 'end' => 52]],
        13 => [['surah' => 12, 'start' => 53, 'end' => 111], ['surah' => 13, 'start' => 1, 'end' => 43], ['surah' => 14, 'start' => 1, 'end' => 52]],
        14 => [['surah' => 15, 'start' => 1, 'end' => 99], ['surah' => 16, 'start' => 1, 'end' => 128]],
        15 => [['surah' => 17, 'start' => 1, 'end' => 111], ['surah' => 18, 'start' => 1, 'end' => 74]],
        16 => [['surah' => 18, 'start' => 75, 'end' => 110], ['surah' => 19, 'start' => 1, 'end' => 98], ['surah' => 20, 'start' => 1, 'end' => 135]],
        17 => [['surah' => 21, 'start' => 1, 'end' => 112], ['surah' => 22, 'start' => 1, 'end' => 78]],
        18 => [['surah' => 23, 'start' => 1, 'end' => 118], ['surah' => 24, 'start' => 1, 'end' => 64], ['surah' => 25, 'start' => 1, 'end' => 20]],
        19 => [['surah' => 25, 'start' => 21, 'end' => 77], ['surah' => 26, 'start' => 1, 'end' => 227], ['surah' => 27, 'start' => 1, 'end' => 55]],
        20 => [['surah' => 27, 'start' => 56, 'end' => 93], ['surah' => 28, 'start' => 1, 'end' => 88], ['surah' => 29, 'start' => 1, 'end' => 45]],
        21 => [['surah' => 29, 'start' => 46, 'end' => 69], ['surah' => 30, 'start' => 1, 'end' => 60], ['surah' => 31, 'start' => 1, 'end' => 34], ['surah' => 32, 'start' => 1, 'end' => 30], ['surah' => 33, 'start' => 1, 'end' => 30]],
        22 => [['surah' => 33, 'start' => 31, 'end' => 73], ['surah' => 34, 'start' => 1, 'end' => 54], ['surah' => 35, 'start' => 1, 'end' => 45], ['surah' => 36, 'start' => 1, 'end' => 27]],
        23 => [['surah' => 36, 'start' => 28, 'end' => 83], ['surah' => 37, 'start' => 1, 'end' => 182], ['surah' => 38, 'start' => 1, 'end' => 88], ['surah' => 39, 'start' => 1, 'end' => 31]],
        24 => [['surah' => 39, 'start' => 32, 'end' => 75], ['surah' => 40, 'start' => 1, 'end' => 85], ['surah' => 41, 'start' => 1, 'end' => 46]],
        25 => [['surah' => 41, 'start' => 47, 'end' => 54], ['surah' => 42, 'start' => 1, 'end' => 53], ['surah' => 43, 'start' => 1, 'end' => 89], ['surah' => 44, 'start' => 1, 'end' => 59], ['surah' => 45, 'start' => 1, 'end' => 37]],
        26 => [['surah' => 46, 'start' => 1, 'end' => 35], ['surah' => 47, 'start' => 1, 'end' => 38], ['surah' => 48, 'start' => 1, 'end' => 29], ['surah' => 49, 'start' => 1, 'end' => 18], ['surah' => 50, 'start' => 1, 'end' => 45], ['surah' => 51, 'start' => 1, 'end' => 30]],
        27 => [['surah' => 51, 'start' => 31, 'end' => 60], ['surah' => 52, 'start' => 1, 'end' => 49], ['surah' => 53, 'start' => 1, 'end' => 62], ['surah' => 54, 'start' => 1, 'end' => 55], ['surah' => 55, 'start' => 1, 'end' => 78], ['surah' => 56, 'start' => 1, 'end' => 96], ['surah' => 57, 'start' => 1, 'end' => 29]],
        28 => [['surah' => 58, 'start' => 1, 'end' => 22], ['surah' => 59, 'start' => 1, 'end' => 24], ['surah' => 60, 'start' => 1, 'end' => 13], ['surah' => 61, 'start' => 1, 'end' => 14], ['surah' => 62, 'start' => 1, 'end' => 11], ['surah' => 63, 'start' => 1, 'end' => 11], ['surah' => 64, 'start' => 1, 'end' => 18], ['surah' => 65, 'start' => 1, 'end' => 12], ['surah' => 66, 'start' => 1, 'end' => 12]],
        29 => [['surah' => 67, 'start' => 1, 'end' => 30], ['surah' => 68, 'start' => 1, 'end' => 52], ['surah' => 69, 'start' => 1, 'end' => 52], ['surah' => 70, 'start' => 1, 'end' => 44], ['surah' => 71, 'start' => 1, 'end' => 28], ['surah' => 72, 'start' => 1, 'end' => 28], ['surah' => 73, 'start' => 1, 'end' => 20], ['surah' => 74, 'start' => 1, 'end' => 56], ['surah' => 75, 'start' => 1, 'end' => 40], ['surah' => 76, 'start' => 1, 'end' => 31], ['surah' => 77, 'start' => 1, 'end' => 50]],
        30 => [
            ['surah' => 78, 'start' => 1, 'end' => 40], ['surah' => 79, 'start' => 1, 'end' => 46], ['surah' => 80, 'start' => 1, 'end' => 42], ['surah' => 81, 'start' => 1, 'end' => 29], ['surah' => 82, 'start' => 1, 'end' => 19], ['surah' => 83, 'start' => 1, 'end' => 36], ['surah' => 84, 'start' => 1, 'end' => 25], ['surah' => 85, 'start' => 1, 'end' => 22], ['surah' => 86, 'start' => 1, 'end' => 17], ['surah' => 87, 'start' => 1, 'end' => 19], ['surah' => 88, 'start' => 1, 'end' => 26], ['surah' => 89, 'start' => 1, 'end' => 30], ['surah' => 90, 'start' => 1, 'end' => 20], ['surah' => 91, 'start' => 1, 'end' => 15], ['surah' => 92, 'start' => 1, 'end' => 21], ['surah' => 93, 'start' => 1, 'end' => 11], ['surah' => 94, 'start' => 1, 'end' => 8],  ['surah' => 95, 'start' => 1, 'end' => 8],  ['surah' => 96, 'start' => 1, 'end' => 19], ['surah' => 97, 'start' => 1, 'end' => 5],  ['surah' => 98, 'start' => 1, 'end' => 8],  ['surah' => 99, 'start' => 1, 'end' => 8],  ['surah' => 100, 'start' => 1, 'end' => 11], ['surah' => 101, 'start' => 1, 'end' => 11], ['surah' => 102, 'start' => 1, 'end' => 8],  ['surah' => 103, 'start' => 1, 'end' => 3],  ['surah' => 104, 'start' => 1, 'end' => 9],  ['surah' => 105, 'start' => 1, 'end' => 5],  ['surah' => 106, 'start' => 1, 'end' => 4],  ['surah' => 107, 'start' => 1, 'end' => 7],  ['surah' => 108, 'start' => 1, 'end' => 3],  ['surah' => 109, 'start' => 1, 'end' => 6],  ['surah' => 110, 'start' => 1, 'end' => 3],  ['surah' => 111, 'start' => 1, 'end' => 5],  ['surah' => 112, 'start' => 1, 'end' => 4],  ['surah' => 113, 'start' => 1, 'end' => 5],  ['surah' => 114, 'start' => 1, 'end' => 6],
        ],
    ];

    private function getJuzStats(Student $student): array
    {
        $surahMap = [];
        try {
            $surahs = DB::table('surahs')->select('id', 'number')->get();
            foreach ($surahs as $s) {
                $surahMap[$s->id] = (int) $s->number;
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        if (self::$allAyahs === null) {
            self::$allAyahs = [];
            self::$juzTotalAyahs = [];

            try {
                $ayahs = DB::table('ayahs')
                    ->select('id', 'surah_id', 'ayah_number', 'juz')
                    ->whereNotNull('juz')
                    ->get();

                foreach ($ayahs as $ayah) {
                    $sNum = $surahMap[$ayah->surah_id] ?? (int) $ayah->surah_id;
                    self::$allAyahs[$sNum][$ayah->ayah_number] = (int) $ayah->juz;
                    if (! isset(self::$juzTotalAyahs[$ayah->juz])) {
                        self::$juzTotalAyahs[$ayah->juz] = 0;
                    }
                    self::$juzTotalAyahs[$ayah->juz]++;
                }
            } catch (\Throwable $e) {
                // Table ayahs not found or query failed
            }

            // Fallback to standard mapping if ayahs table is empty or missing
            if (empty(self::$allAyahs)) {
                foreach (self::$standardJuzRanges as $juz => $ranges) {
                    self::$juzTotalAyahs[$juz] = self::$juzAyahCounts[$juz] ?? 0;
                    foreach ($ranges as $range) {
                        for ($a = $range['start']; $a <= $range['end']; $a++) {
                            self::$allAyahs[$range['surah']][$a] = $juz;
                        }
                    }
                }
            }
        }

        $passedRecords = HafalanRecord::where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereNotNull('surah_id')
            ->whereNotNull('ayah_start')
            ->whereNotNull('ayah_end')
            ->get(['surah_id', 'ayah_start', 'ayah_end']);

        $ummiRecords = \App\Models\UmmiRecord::where('student_id', $student->id)
            ->whereNotNull('hafalan_surah_id')
            ->whereNotNull('hafalan_ayah')
            ->get(['hafalan_surah_id', 'hafalan_ayah']);

        $juzMemorizedCount = [];
        $memorizedMap = [];

        foreach ($passedRecords as $record) {
            $start = (int) $record->ayah_start;
            $end = (int) $record->ayah_end;
            $surahNum = $surahMap[$record->surah_id] ?? (int) $record->surah_id;

            for ($a = $start; $a <= $end; $a++) {
                if (isset(self::$allAyahs[$surahNum][$a])) {
                    $juz = self::$allAyahs[$surahNum][$a];
                    $key = "{$surahNum}-{$a}";
                    if (! isset($memorizedMap[$key])) {
                        $memorizedMap[$key] = true;
                        if (! isset($juzMemorizedCount[$juz])) {
                            $juzMemorizedCount[$juz] = 0;
                        }
                        $juzMemorizedCount[$juz]++;
                    }
                }
            }
        }

        foreach ($ummiRecords as $uRec) {
            $clean = str_replace(' ', '', (string) $uRec->hafalan_ayah);
            if (preg_match('/(\d+)\s*[-–—]\s*(\d+)/u', $clean, $m)) {
                $start = (int) $m[1];
                $end = (int) $m[2];
            } elseif (is_numeric($clean)) {
                $start = (int) $clean;
                $end = (int) $clean;
            } else {
                continue;
            }

            $surahNum = $surahMap[$uRec->hafalan_surah_id] ?? (int) $uRec->hafalan_surah_id;

            for ($a = $start; $a <= $end; $a++) {
                if (isset(self::$allAyahs[$surahNum][$a])) {
                    $juz = self::$allAyahs[$surahNum][$a];
                    $key = "{$surahNum}-{$a}";
                    if (! isset($memorizedMap[$key])) {
                        $memorizedMap[$key] = true;
                        if (! isset($juzMemorizedCount[$juz])) {
                            $juzMemorizedCount[$juz] = 0;
                        }
                        $juzMemorizedCount[$juz]++;
                    }
                }
            }
        }

        $completedJuz = [];
        foreach (self::$juzTotalAyahs as $juz => $total) {
            $memorized = $juzMemorizedCount[$juz] ?? 0;
            if ($memorized >= $total && $total > 0) {
                $completedJuz[] = $juz;
            }
        }

        sort($completedJuz);

        return [
            'completed_juz' => $completedJuz,
            'juz_count' => count($completedJuz),
            'juz_memorized_count' => $juzMemorizedCount,
            'juz_total_ayahs' => self::$juzTotalAyahs,
        ];
    }

    public function summaryFromRows(Collection $rows): array
    {
        return [
            'total_students' => $rows->count(),
            'total_memorized_ayahs' => (int) $rows->sum('memorized_ayahs'),
            'total_hafalan_records' => (int) $rows->sum('total_hafalan_records'),
            'total_murajaah_records' => (int) $rows->sum('total_murajaah_records'),
            'total_active_targets' => (int) $rows->sum('active_targets'),
            'total_overdue_targets' => (int) $rows->sum('overdue_targets'),
            'average_progress_percent' => round((float) $rows->avg('progress_percent'), 2),
            'average_hafalan_score' => round((float) $rows->avg('average_hafalan_score'), 2),
            'average_murajaah_score' => round((float) $rows->avg('average_murajaah_score'), 2),
        ];
    }

    private function memorizedAyahCount(Student $student): int
    {
        $records = HafalanRecord::query()
            ->where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereNotNull('surah_id')
            ->whereNotNull('ayah_start')
            ->whereNotNull('ayah_end')
            ->get(['surah_id', 'ayah_start', 'ayah_end']);

        $ummiRecords = \App\Models\UmmiRecord::query()
            ->where('student_id', $student->id)
            ->whereNotNull('hafalan_surah_id')
            ->whereNotNull('hafalan_ayah')
            ->get(['hafalan_surah_id', 'hafalan_ayah']);

        if ($records->isEmpty() && $ummiRecords->isEmpty()) {
            return 0;
        }

        $surahTotals = Surah::query()
            ->pluck('total_ayah', 'id');

        $surahIntervals = [];

        foreach ($records as $record) {
            $surahId = $record->surah_id;
            $surahTotalAyah = (int) ($surahTotals[$surahId] ?? 0);
            if ($surahTotalAyah <= 0) {
                continue;
            }

            $start = max(1, (int) $record->ayah_start);
            $end = min($surahTotalAyah, (int) $record->ayah_end);

            if ($start <= $end) {
                $surahIntervals[$surahId][] = [$start, $end];
            }
        }

        foreach ($ummiRecords as $uRec) {
            $surahId = $uRec->hafalan_surah_id;
            $surahTotalAyah = (int) ($surahTotals[$surahId] ?? 0);
            if ($surahTotalAyah <= 0) {
                continue;
            }

            $clean = str_replace(' ', '', (string) $uRec->hafalan_ayah);
            if (preg_match('/(\d+)\s*[-–—]\s*(\d+)/u', $clean, $m)) {
                $start = (int) $m[1];
                $end = (int) $m[2];
            } elseif (is_numeric($clean)) {
                $start = (int) $clean;
                $end = (int) $clean;
            } else {
                continue;
            }

            $start = max(1, $start);
            $end = min($surahTotalAyah, $end);

            if ($start <= $end) {
                $surahIntervals[$surahId][] = [$start, $end];
            }
        }

        $total = 0;

        foreach ($surahIntervals as $surahId => $intervals) {
            $total += $this->countMergedIntervals($intervals);
        }

        return $total;
    }

    private function countMergedIntervals(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            if (empty($merged)) {
                $merged[] = [$start, $end];

                continue;
            }

            $lastIndex = count($merged) - 1;

            if ($start <= $merged[$lastIndex][1] + 1) {
                $merged[$lastIndex][1] = max($merged[$lastIndex][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        $count = 0;

        foreach ($merged as [$start, $end]) {
            $count += ($end - $start + 1);
        }

        return $count;
    }

    private function averageMurajaahScore(Student $student): float
    {
        // murajaah_records.overall_score selalu ada (confirmed dari migration)
        return round((float) MurajaahRecord::query()
            ->where('student_id', $student->id)
            ->avg('overall_score'), 2);
    }

    private function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            if (($user->role?->name ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    public function getTermMilestones(Student $student): array
    {
        try {
            $firstHafalan = HafalanRecord::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->where(function ($q) {
                    $q->where('status', 'passed')->orWhereNull('status');
                })
                ->orderBy('submitted_at', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $firstUmmi = \App\Models\UmmiRecord::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->orderBy('tanggal', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $firstRecord = null;
            if ($firstHafalan && $firstUmmi) {
                $hafDate = \Carbon\Carbon::parse($firstHafalan->submitted_at);
                $ummiDate = \Carbon\Carbon::parse($firstUmmi->tanggal);
                if ($hafDate->lte($ummiDate)) {
                    $firstRecord = [
                        'type' => 'hafalan',
                        'date' => $firstHafalan->submitted_at?->format('d M Y'),
                        'raw_date' => $firstHafalan->submitted_at,
                        'title' => ($firstHafalan->surah?->name_latin ?? 'Surah #'.$firstHafalan->surah_id).' (Ayat '.$firstHafalan->ayah_start.' - '.$firstHafalan->ayah_end.')',
                    ];
                } else {
                    $uTitle = $firstUmmi->surah?->name_latin
                        ? ($firstUmmi->surah->name_latin . ' (Ayat ' . ($firstUmmi->hafalan_ayah ?: '-') . ')')
                        : (($firstUmmi->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($firstUmmi->ummi_halaman ?? '-') . ')');
                    $firstRecord = [
                        'type' => 'ummi',
                        'date' => $firstUmmi->tanggal?->format('d M Y'),
                        'raw_date' => $firstUmmi->tanggal,
                        'title' => $uTitle,
                    ];
                }
            } elseif ($firstHafalan) {
                $firstRecord = [
                    'type' => 'hafalan',
                    'date' => $firstHafalan->submitted_at?->format('d M Y'),
                    'raw_date' => $firstHafalan->submitted_at,
                    'title' => ($firstHafalan->surah?->name_latin ?? 'Surah #'.$firstHafalan->surah_id).' (Ayat '.$firstHafalan->ayah_start.' - '.$firstHafalan->ayah_end.')',
                ];
            } elseif ($firstUmmi) {
                $uTitle = $firstUmmi->surah?->name_latin
                    ? ($firstUmmi->surah->name_latin . ' (Ayat ' . ($firstUmmi->hafalan_ayah ?: '-') . ')')
                    : (($firstUmmi->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($firstUmmi->ummi_halaman ?? '-') . ')');
                $firstRecord = [
                    'type' => 'ummi',
                    'date' => $firstUmmi->tanggal?->format('d M Y'),
                    'raw_date' => $firstUmmi->tanggal,
                    'title' => $uTitle,
                ];
            }

            $currentMonth = (int) date('n');
            $currentYear = (int) date('Y');
            $currentSchoolYearStart = $currentMonth >= 7 ? $currentYear : $currentYear - 1;

            $className = $student->classRoom?->name ?? '';
            $level = (int) ($student->classRoom?->level ?? 0);

            $isGrade12 = (bool) ($level === 12 || preg_match('/\bXII\b/i', $className) || preg_match('/\b12\b/i', $className));
            $isGrade11 = (bool) ($level === 11 || (preg_match('/\bXI\b/i', $className) && !preg_match('/\bXII\b/i', $className)) || preg_match('/\b11\b/i', $className));

            $currentGradeNum = 10;
            if ($isGrade12) {
                $currentGradeNum = 12;
            } elseif ($isGrade11) {
                $currentGradeNum = 11;
            }

            $grades = [
                10 => ['code' => 'X', 'name' => 'Kelas 10', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 10)],
                11 => ['code' => 'XI', 'name' => 'Kelas 11', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 11)],
                12 => ['code' => 'XII', 'name' => 'Kelas 12', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 12)],
            ];

            $todayStr = now()->toDateString();
            $journey = [];

            foreach ($grades as $gNum => $gInfo) {
                $syStart = $gInfo['year_start'];
                $syEnd = $syStart + 1;

                $terms = [
                    1 => ['name' => 'Term 1 (Jul - Sep)', 'start' => "{$syStart}-07-01", 'end' => "{$syStart}-09-30"],
                    2 => ['name' => 'Term 2 (Okt - Des)', 'start' => "{$syStart}-10-01", 'end' => "{$syStart}-12-31"],
                    3 => ['name' => 'Term 3 (Jan - Mar)', 'start' => "{$syEnd}-01-01", 'end' => "{$syEnd}-03-31"],
                    4 => ['name' => 'Term 4 (Apr - Jun)', 'start' => "{$syEnd}-04-01", 'end' => "{$syEnd}-06-30"],
                ];

                $termResults = [];

                foreach ($terms as $tNum => $tInfo) {
                    $tStart = $tInfo['start'];
                    $tEnd = $tInfo['end'];
                    $isCurrent = ($todayStr >= $tStart && $todayStr <= $tEnd);

                    $hafalanInTerm = HafalanRecord::query()
                        ->with('surah')
                        ->where('student_id', $student->id)
                        ->where(function ($q) {
                            $q->where('status', 'passed')->orWhereNull('status');
                        })
                        ->whereDate('submitted_at', '>=', $tStart)
                        ->whereDate('submitted_at', '<=', $tEnd)
                        ->orderBy('submitted_at', 'asc')
                        ->get();

                    $ummiInTerm = \App\Models\UmmiRecord::query()
                        ->with('surah')
                        ->where('student_id', $student->id)
                        ->whereDate('tanggal', '>=', $tStart)
                        ->whereDate('tanggal', '<=', $tEnd)
                        ->orderBy('tanggal', 'asc')
                        ->get();

                    $firstH = $hafalanInTerm->first();
                    $lastH = $hafalanInTerm->last();

                    $firstU = $ummiInTerm->first();
                    $lastU = $ummiInTerm->last();

                    $termFirst = null;
                    $termLast = null;

                    // Format Reguler
                    $formatH = function ($h) {
                        return [
                            'date' => $h->submitted_at?->format('d/m/Y'),
                            'surah_name' => $h->surah?->name_latin ?? '-',
                            'ayah_range' => 'Ayat '.$h->ayah_start.'-'.$h->ayah_end,
                            'full_text' => ($h->surah?->name_latin ?? '-').' (Ayat '.$h->ayah_start.'-'.$h->ayah_end.')',
                        ];
                    };

                    // Format Ummi
                    $formatU = function ($u) {
                        $fullStr = $u->surah?->name_latin
                            ? ($u->surah->name_latin . ' (Ayat ' . ($u->hafalan_ayah ?: '-') . ')')
                            : (($u->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($u->ummi_halaman ?? '-') . ')');
                        return [
                            'date' => $u->tanggal?->format('d/m/Y'),
                            'surah_name' => $u->surah?->name_latin ?? ($u->ummi_jilid ?? 'Ummi'),
                            'ayah_range' => $u->hafalan_ayah ? ('Ayat '.$u->hafalan_ayah) : ('Hal. '.($u->ummi_halaman ?? '-')),
                            'full_text' => $fullStr,
                        ];
                    };

                    if ($firstH && $firstU) {
                        $hDate = \Carbon\Carbon::parse($firstH->submitted_at);
                        $uDate = \Carbon\Carbon::parse($firstU->tanggal);
                        $termFirst = $hDate->lte($uDate) ? $formatH($firstH) : $formatU($firstU);
                    } elseif ($firstH) {
                        $termFirst = $formatH($firstH);
                    } elseif ($firstU) {
                        $termFirst = $formatU($firstU);
                    }

                    if ($lastH && $lastU) {
                        $hDate = \Carbon\Carbon::parse($lastH->submitted_at);
                        $uDate = \Carbon\Carbon::parse($lastU->tanggal);
                        $termLast = $hDate->gte($uDate) ? $formatH($lastH) : $formatU($lastU);
                    } elseif ($lastH) {
                        $termLast = $formatH($lastH);
                    } elseif ($lastU) {
                        $termLast = $formatU($lastU);
                    }

                    $totalLines = $hafalanInTerm->sum('lines_count') + $ummiInTerm->sum('lines_count');

                    // Target per Term
                    $targetsInTerm = HafalanTarget::query()
                        ->with('surah')
                        ->where('student_id', $student->id)
                        ->whereDate('target_date', '>=', $tStart)
                        ->whereDate('target_date', '<=', $tEnd)
                        ->orderBy('target_date', 'asc')
                        ->get();

                    $termTarget = null;
                    if ($firstTarget = $targetsInTerm->first()) {
                        if ($firstTarget->ummi_jilid || $gNum === 10) {
                            $tStr = '📗 ' . ($firstTarget->ummi_jilid ?? 'Target Ummi');
                            if ($firstTarget->halaman_peraga || $firstTarget->halaman_buku) {
                                $tStr .= ' (Peraga: ' . ($firstTarget->halaman_peraga ?? '-') . ', Buku: ' . ($firstTarget->halaman_buku ?? '-') . ')';
                            }
                            if ($firstTarget->surah) {
                                $tStr .= ' · Surah ' . $firstTarget->surah->name_latin;
                            }
                        } else {
                            $tStr = '📘 ' . ($firstTarget->surah?->name_latin ?? 'Target Reguler') . ' (Ayat ' . $firstTarget->ayah_start . '-' . $firstTarget->ayah_end . ')';
                        }

                        $termTarget = [
                            'full_text' => $tStr,
                            'date' => $firstTarget->target_date?->format('d/m/Y'),
                            'status' => $firstTarget->status_label,
                        ];
                    }

                    $termResults[$tNum] = [
                        'term_number' => $tNum,
                        'name' => $tInfo['name'],
                        'start_date' => $tStart,
                        'end_date' => $tEnd,
                        'is_current' => $isCurrent,
                        'has_data' => ($termFirst !== null),
                        'target' => $termTarget,
                        'first_setoran' => $termFirst,
                        'last_setoran' => $termLast,
                        'total_records' => $hafalanInTerm->count() + $ummiInTerm->count(),
                        'total_lines' => $totalLines,
                    ];
                }

                $journey[] = [
                    'grade_num' => $gNum,
                    'grade_name' => $gInfo['name'],
                    'school_year' => "{$syStart}/{$syEnd}",
                    'is_current_grade' => ($gNum === $currentGradeNum),
                    'terms' => $termResults,
                ];
            }

            return [
                'first_record' => $firstRecord,
                'journey' => $journey,
            ];
        } catch (\Throwable $e) {
            return [
                'first_record' => null,
                'journey' => [],
            ];
        }
    }
}
