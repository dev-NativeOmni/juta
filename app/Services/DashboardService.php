<?php

namespace App\Services;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private readonly StudentProgressService $studentProgressService,
        private readonly StudentMotivationService $studentMotivationService
    ) {
        //
    }

    public function adminStats(): array
    {
        try {
            return Cache::remember('admin_dashboard_stats', 60, function () {
                $today = now()->toDateString();

                $activeStudents = Student::query()
                    ->with([
                        'classRoom.program',
                        'teacher.user',
                    ])
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get();

                return [
                    'total_students' => Student::query()->count(),
                    'active_students' => Student::query()->where('status', 'active')->count(),
                    'inactive_students' => Student::query()->where('status', 'inactive')->count(),
                    'graduated_students' => Student::query()->where('status', 'graduated')->count(),

                'total_teachers' => TeacherProfile::query()->count(),
                'total_parents' => ParentProfile::query()->count(),
                'total_programs' => Program::query()->count(),
                'total_class_rooms' => ClassRoom::query()->count(),

                'hafalan_today' => HafalanRecord::query()
                    ->whereDate('submitted_at', $today)
                    ->count(),

                'murajaah_today' => MurajaahRecord::query()
                    ->whereDate('reviewed_at', $today)
                    ->count(),

                'active_targets' => HafalanTarget::query()
                    ->where('status', 'active')
                    ->count(),

                'overdue_targets' => HafalanTarget::query()
                    ->where('status', 'active')
                    ->whereDate('target_date', '<', $today)
                    ->count(),

                'completed_targets' => HafalanTarget::query()
                    ->where('status', 'completed')
                    ->count(),

                'hafalan_need_attention' => HafalanRecord::query()
                    ->whereIn('status', [
                        'repeat',
                        'needs_improvement',
                    ])
                    ->count(),

                'murajaah_need_attention' => MurajaahRecord::query()
                    ->whereIn('status', [
                        'repeat',
                        'needs_improvement',
                    ])
                    ->count(),

                'latest_hafalan_records' => HafalanRecord::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->latest('submitted_at')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'latest_murajaah_records' => MurajaahRecord::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->latest('reviewed_at')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'latest_targets' => HafalanTarget::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->orderBy('target_date')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'students_progress' => $this->studentsProgress($activeStudents)->take(10),
            ];
        });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function teacherStats(User $user): array
    {
        try {
            $teacher = $user->teacherProfile
                ?? TeacherProfile::query()->where('user_id', $user->id)->first();

            if (! $teacher) {
                $teacher = TeacherProfile::query()
                    ->whereHas('user', function ($q) use ($user) {
                        $q->where('name', 'like', '%'.$user->name.'%')
                          ->orWhere('username', $user->username);
                    })
                    ->first();

                if (! $teacher) {
                    $teacher = TeacherProfile::query()->whereNull('user_id')->first();
                }

                if ($teacher && ! $teacher->user_id) {
                    $teacher->update(['user_id' => $user->id]);
                } elseif (! $teacher) {
                    $teacher = TeacherProfile::create(['user_id' => $user->id]);
                }

                $user->setRelation('teacherProfile', $teacher);
            }

            $today = now()->toDateString();

            $students = $teacher->students()
                ->with([
                    'classRoom.program',
                    'teacher.user',
                ])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            $studentIds = $students->pluck('id');

            $hafalanToday = HafalanRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereDate('submitted_at', $today)
                ->count();

            $murajaahToday = MurajaahRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereDate('reviewed_at', $today)
                ->count();

            $hafalanNeedAttention = HafalanRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count();

            $murajaahNeedAttention = MurajaahRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count();

            return [
                'teacher' => $teacher,
                'students' => $students,
                'students_progress' => $this->studentsProgress($students),
                'total_students' => $students->count(),

                'hafalan_today' => $hafalanToday,
                'murajaah_today' => $murajaahToday,
                'hafalan_need_attention' => $hafalanNeedAttention,
                'murajaah_need_attention' => $murajaahNeedAttention,

                'active_targets' => HafalanTarget::query()
                    ->whereIn('student_id', $studentIds)
                    ->where('status', 'active')
                    ->count(),

                'overdue_targets' => HafalanTarget::query()
                    ->whereIn('student_id', $studentIds)
                    ->where('status', 'active')
                    ->whereDate('target_date', '<', $today)
                    ->count(),

                'latest_targets' => HafalanTarget::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->whereIn('student_id', $studentIds)
                    ->orderBy('target_date')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'latest_hafalan_records' => HafalanRecord::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->whereIn('student_id', $studentIds)
                    ->latest('submitted_at')
                    ->latest()
                    ->limit(8)
                    ->get(),

                'latest_murajaah_records' => MurajaahRecord::query()
                    ->with([
                        'student.classRoom.program',
                        'teacher.user',
                        'surah',
                    ])
                    ->whereIn('student_id', $studentIds)
                    ->latest('reviewed_at')
                    ->latest()
                    ->limit(8)
                    ->get(),
            ];
        } catch (\Throwable $e) {
            return [
                'teacher' => $user->teacherProfile,
                'students' => collect(),
                'students_progress' => collect(),
                'total_students' => 0,
                'hafalan_today' => 0,
                'murajaah_today' => 0,
                'hafalan_need_attention' => 0,
                'murajaah_need_attention' => 0,
                'active_targets' => 0,
                'overdue_targets' => 0,
                'latest_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
            ];
        }
    }

    public function parentStats(User $user): array
    {
        $parent = $user->parentProfile;

        if (! $parent) {
            $parent = ParentProfile::create(['user_id' => $user->id]);
            $user->setRelation('parentProfile', $parent);
        }

        $today = now()->toDateString();
        $year = (int) date('Y');
        $month = (int) date('n');

        $children = $parent->students()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('students.status', 'active')
            ->orderBy('students.name')
            ->get();

        $studentIds = $children->pluck('id');

        $childrenProfiles = $children->map(function (Student $student) use ($year, $month, $today) {
            $progress = $this->studentProgressService->calculate($student);
            $progressWithAliases = $this->progressAliases($student, $progress);

            // Adab summary
            $adabScoreData = Setting::calculateAdabScore($student->id, $year, $month);
            $todayAdabRecord = AdabRecord::where('student_id', $student->id)
                ->where('assessment_date', $today)
                ->first();

            // Tanse / Poin Kedisiplinan & Prestasi
            $violationPoints = (int) StudentPoint::where('student_id', $student->id)
                ->whereIn('type', ['violation', 'lateness', 'attribute'])
                ->sum('points');
            $rewardPoints = (int) StudentPoint::where('student_id', $student->id)
                ->where('type', 'reward')
                ->sum('points');
            $recentPoints = StudentPoint::where('student_id', $student->id)
                ->latest('date')
                ->take(5)
                ->get();

            // Recent Hafalan & Murajaah for this student specifically
            $recentHafalan = HafalanRecord::with('surah')
                ->where('student_id', $student->id)
                ->latest('submitted_at')
                ->take(5)
                ->get();

            $recentMurajaah = MurajaahRecord::with('surah')
                ->where('student_id', $student->id)
                ->latest('reviewed_at')
                ->take(5)
                ->get();

            $recentTargets = HafalanTarget::with('surah')
                ->where('student_id', $student->id)
                ->orderByRaw("CASE WHEN status IN ('active', 'planned', 'in_progress') THEN 0 ELSE 1 END")
                ->orderBy('target_date')
                ->take(5)
                ->get();

            return array_merge($progressWithAliases, [
                'adab' => [
                    'attendance_rate' => $adabScoreData['attendance_rate'],
                    'mentor_score' => $adabScoreData['mentor_score'],
                    'final_score' => $adabScoreData['final_score'],
                    'grade' => $adabScoreData['grade'],
                    'grade_label' => $adabScoreData['grade_label'],
                    'today_record' => $todayAdabRecord,
                ],
                'tanse' => [
                    'violation_points' => $violationPoints,
                    'reward_points' => $rewardPoints,
                    'net_points' => $rewardPoints - $violationPoints,
                    'recent_points' => $recentPoints,
                ],
                'student_hafalan' => $recentHafalan,
                'student_murajaah' => $recentMurajaah,
                'student_targets' => $recentTargets,
            ]);
        });

        return [
            'parent' => $parent,
            'children' => $children,
            'children_progress' => $childrenProfiles,
            'children_motivation' => $this->studentsMotivation($children),
            'total_children' => $children->count(),

            'active_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->count(),

            'overdue_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->whereDate('target_date', '<', $today)
                ->count(),

            'latest_targets' => HafalanTarget::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->orderBy('target_date')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_hafalan_records' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('submitted_at')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_murajaah_records' => MurajaahRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('reviewed_at')
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    public function studentStats(User $user): array
    {
        $student = $user->studentProfile;

        if (! $student) {
            $student = Student::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'status' => 'active',
            ]);
            $user->setRelation('studentProfile', $student);
        }

        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $today = now()->toDateString();
        $progress = $this->studentProgressService->calculate($student);

        $activeTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->orderBy('target_date')
            ->get();

        $overdueTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('target_date', '<', $today)
            ->orderBy('target_date')
            ->get();

        $latestTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->orderBy('target_date')
            ->latest()
            ->limit(8)
            ->get();

        $latestHafalanRecords = HafalanRecord::query()
            ->with([
                'teacher.user',
                'surah',
            ])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->limit(8)
            ->get();

        $latestMurajaahRecords = MurajaahRecord::query()
            ->with([
                'teacher.user',
                'surah',
            ])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->limit(8)
            ->get();

        return [
            'student' => $student,
            'progress' => $progress,
            'summary' => $this->progressAliases($student, $progress),
            'motivation' => $this->studentMotivationService->build($student, $progress),
            'active_targets' => $activeTargets,
            'overdue_targets' => $overdueTargets,
            'latest_targets' => $latestTargets,
            'latest_hafalan_records' => $latestHafalanRecords,
            'latest_murajaah_records' => $latestMurajaahRecords,
        ];
    }

    private function studentsProgress(Collection $students): Collection
    {
        $sample = $students->count() > 25 ? $students->take(25) : $students;

        return $sample
            ->map(function (Student $student) {
                $progress = $this->studentProgressService->calculate($student);

                return $this->progressAliases($student, $progress);
            })
            ->sortByDesc('progress_percentage')
            ->values();
    }

    private function studentsMotivation(Collection $students): Collection
    {
        return $students
            ->map(function (Student $student) {
                $progress = $this->studentProgressService->calculate($student);

                return [
                    'student' => $student,
                    'progress' => $progress,
                    'motivation' => $this->studentMotivationService->build($student, $progress),
                ];
            })
            ->values();
    }

    private function progressAliases(Student $student, array $progress): array
    {
        $progressPercent = (float) ($progress['progress_percent'] ?? 0);
        $memorizedAyahs = (int) ($progress['memorized_ayahs'] ?? 0);
        $totalQuranAyahs = (int) ($progress['total_quran_ayahs'] ?? 6236);

        return array_merge($progress, [
            'student' => $student,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_number' => $student->student_number,
            'class_room_name' => $student->classRoom?->name,
            'program_name' => $student->classRoom?->program?->name,

            'memorized_ayah_count' => $memorizedAyahs,
            'total_ayah_count' => $totalQuranAyahs,
            'progress_percentage' => $progressPercent,

            'memorized_ayahs' => $memorizedAyahs,
            'total_quran_ayahs' => $totalQuranAyahs,
            'progress_percent' => $progressPercent,

            'total_hafalan_records' => (int) ($progress['total_hafalan_records'] ?? 0),
            'total_murajaah_records' => (int) ($progress['total_murajaah_records'] ?? 0),
            'active_targets' => (int) ($progress['active_targets'] ?? 0),
            'overdue_targets' => (int) ($progress['overdue_targets'] ?? 0),
            'average_hafalan_score' => (float) ($progress['average_hafalan_score'] ?? 0),
            'average_murajaah_score' => (float) ($progress['average_murajaah_score'] ?? 0),
        ]);
    }
}
