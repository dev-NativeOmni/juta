<?php

namespace App\Services\Api\V1;

use App\Models\ClassRoom;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Student;
use App\Models\SystemNotification;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardApiService
{
    public function __construct(
        private readonly StudentApiService $studentApiService
    ) {}

    public function build(User $user, string $dashboard): array
    {
        $studentIds = $this->visibleStudentIds($user);

        return match ($dashboard) {
            'admin' => $this->adminSummary($user, $studentIds),
            'teacher' => $this->teacherSummary($user, $studentIds),
            'parent' => $this->parentSummary($user, $studentIds),
            'student' => $this->studentSummary($user, $studentIds),
            default => $this->basePayload($user, $dashboard),
        };
    }

    public function canAccessDashboard(User $user, string $dashboard): bool
    {
        return match ($dashboard) {
            'admin' => $this->userHasAnyRole($user, ['super_admin', 'admin']),
            'teacher' => $this->userHasAnyRole($user, ['teacher']),
            'parent' => $this->userHasAnyRole($user, ['parent']),
            'student' => $this->userHasAnyRole($user, ['student']),
            default => false,
        };
    }

    private function adminSummary(User $user, Collection $studentIds): array
    {
        $payload = $this->basePayload($user, 'admin');

        $payload['summary'] = array_merge(
            $this->studentAcademicSummary($studentIds),
            [
                'total_programs' => $this->safeCount(Program::class),
                'total_class_rooms' => $this->safeCount(ClassRoom::class),
                'total_teachers' => $this->safeCount(TeacherProfile::class),
                'total_parents' => $this->safeCount(ParentProfile::class),
                'total_users' => $this->safeCount(User::class),
                'unread_notifications' => $this->unreadNotificationCount($user),
            ]
        );

        $payload['today'] = $this->todaySummary($studentIds);
        $payload['progress'] = $this->progressSummary($studentIds);
        $payload['recent'] = $this->recentActivity($studentIds);
        $payload['alerts'] = $this->alerts($studentIds);

        return $payload;
    }

    private function teacherSummary(User $user, Collection $studentIds): array
    {
        $payload = $this->basePayload($user, 'teacher');

        $payload['summary'] = array_merge(
            $this->studentAcademicSummary($studentIds),
            [
                'unread_notifications' => $this->unreadNotificationCount($user),
            ]
        );

        $payload['today'] = $this->todaySummary($studentIds);
        $payload['progress'] = $this->progressSummary($studentIds);
        $payload['recent'] = $this->recentActivity($studentIds);
        $payload['alerts'] = $this->alerts($studentIds);

        return $payload;
    }

    private function parentSummary(User $user, Collection $studentIds): array
    {
        $payload = $this->basePayload($user, 'parent');

        $payload['summary'] = array_merge(
            $this->studentAcademicSummary($studentIds),
            [
                'children_count' => $studentIds->count(),
                'unread_notifications' => $this->unreadNotificationCount($user),
            ]
        );

        $payload['today'] = $this->todaySummary($studentIds);
        $payload['progress'] = $this->progressSummary($studentIds);
        $payload['recent'] = $this->recentActivity($studentIds);
        $payload['alerts'] = $this->alerts($studentIds);

        return $payload;
    }

    private function studentSummary(User $user, Collection $studentIds): array
    {
        $payload = $this->basePayload($user, 'student');

        $payload['summary'] = array_merge(
            $this->studentAcademicSummary($studentIds),
            [
                'unread_notifications' => $this->unreadNotificationCount($user),
            ]
        );

        $payload['today'] = $this->todaySummary($studentIds);
        $payload['progress'] = $this->progressSummary($studentIds);
        $payload['recent'] = $this->recentActivity($studentIds);
        $payload['alerts'] = $this->alerts($studentIds);

        return $payload;
    }

    private function basePayload(User $user, string $dashboard): array
    {
        return [
            'dashboard' => $dashboard,
            'role' => $user->role?->name,
            'generated_at' => now()->toISOString(),
            'summary' => [],
            'progress' => [],
            'today' => [],
            'recent' => [],
            'alerts' => [],
        ];
    }

    private function visibleStudentIds(User $user): Collection
    {
        return $this->studentApiService
            ->visibleStudentQuery($user)
            ->pluck('students.id')
            ->values();
    }

    private function studentAcademicSummary(Collection $studentIds): array
    {
        $ids = $studentIds->all();

        return [
            'total_students' => $studentIds->count(),
            'active_students' => $this->activeStudentCount($ids),

            'total_hafalan_records' => $this->hafalanSurahQuery($ids)->count(),

            'passed_hafalan_records' => $this->hafalanSurahQuery($ids)
                ->where('status', 'passed')
                ->count(),

            'repeat_hafalan_records' => $this->hafalanSurahQuery($ids)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'average_hafalan_score' => round((float) $this->hafalanSurahQuery($ids)
                ->whereNotNull('score')
                ->avg('score'), 2),

            'total_murajaah_records' => MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->count(),

            'passed_murajaah_records' => MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->where('status', 'passed')
                ->count(),

            'repeat_murajaah_records' => MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'average_murajaah_score' => round((float) MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->whereNotNull('overall_score')
                ->avg('overall_score'), 2),

            'total_targets' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->count(),

            'active_targets' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->count(),

            'completed_targets' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->where('status', 'completed')
                ->count(),

            'missed_targets' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->where('status', 'missed')
                ->count(),
        ];
    }

    private function todaySummary(Collection $studentIds): array
    {
        $ids = $studentIds->all();
        $today = now()->toDateString();

        return [
            'date' => $today,

            'hafalan_submitted_today' => $this->hafalanSurahQuery($ids)
                ->whereDate('hafalan_records.submitted_at', $today)
                ->count(),

            'murajaah_reviewed_today' => MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->whereDate('reviewed_at', $today)
                ->count(),

            'targets_due_today' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->whereDate('target_date', $today)
                ->count(),

            'targets_completed_today' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->whereDate('completed_at', $today)
                ->count(),
        ];
    }

    private function progressSummary(Collection $studentIds): array
    {
        $ids = $studentIds->all();

        $totalHafalan = $this->hafalanSurahQuery($ids)->count();

        $passedHafalan = $this->hafalanSurahQuery($ids)
            ->where('status', 'passed')
            ->count();

        $totalMurajaah = MurajaahRecord::query()
            ->whereIn('student_id', $ids)
            ->count();

        $passedMurajaah = MurajaahRecord::query()
            ->whereIn('student_id', $ids)
            ->where('status', 'passed')
            ->count();

        $totalTargets = HafalanTarget::query()
            ->whereIn('student_id', $ids)
            ->count();

        $completedTargets = HafalanTarget::query()
            ->whereIn('student_id', $ids)
            ->where('status', 'completed')
            ->count();

        return [
            'hafalan_pass_rate' => $totalHafalan > 0
                ? round(($passedHafalan / $totalHafalan) * 100, 2)
                : 0,

            'murajaah_pass_rate' => $totalMurajaah > 0
                ? round(($passedMurajaah / $totalMurajaah) * 100, 2)
                : 0,

            'target_completion_rate' => $totalTargets > 0
                ? round(($completedTargets / $totalTargets) * 100, 2)
                : 0,
        ];
    }

    private function recentActivity(Collection $studentIds): array
    {
        $ids = $studentIds->all();

        $latestHafalan = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->with(['hafalanRecord.student', 'surah', 'hafalanRecord.teacher.user'])
            ->whereIn('hafalan_records.student_id', $ids)
            ->orderByDesc('hafalan_records.submitted_at')
            ->orderByDesc('hafalan_record_surahs.id')
            ->limit(5)
            ->get()
            ->map(fn (HafalanRecordSurah $record) => $this->hafalanSummary($record->applyHeaderOverlay()))
            ->values()
            ->all();

        $latestMurajaah = MurajaahRecord::query()
            ->with(['student', 'surah', 'teacher.user'])
            ->whereIn('student_id', $ids)
            ->latest('reviewed_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (MurajaahRecord $record) => $this->murajaahSummary($record))
            ->values()
            ->all();

        $latestTargets = HafalanTarget::query()
            ->with(['student', 'surah', 'teacher.user'])
            ->whereIn('student_id', $ids)
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (HafalanTarget $target) => $this->targetSummary($target))
            ->values()
            ->all();

        return [
            'latest_hafalan_records' => $latestHafalan,
            'latest_murajaah_records' => $latestMurajaah,
            'latest_targets' => $latestTargets,
        ];
    }

    private function alerts(Collection $studentIds): array
    {
        $ids = $studentIds->all();

        return [
            'overdue_targets' => HafalanTarget::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->whereDate('target_date', '<', now()->toDateString())
                ->count(),

            'hafalan_needs_improvement' => $this->hafalanSurahQuery($ids)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'murajaah_needs_improvement' => MurajaahRecord::query()
                ->whereIn('student_id', $ids)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),
        ];
    }

    /**
     * Base query over hafalan_record_surahs (joined to their header) scoped to the
     * given student ids, for counting/averaging per-surah submissions the same way
     * the old flat hafalan_records rows were counted.
     */
    private function hafalanSurahQuery(array $studentIds): Builder
    {
        return HafalanRecordSurah::query()
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->whereIn('hafalan_records.student_id', $studentIds);
    }

    private function activeStudentCount(array $studentIds): int
    {
        $query = Student::query()->whereIn('id', $studentIds);

        if (Schema::hasColumn('students', 'status')) {
            $query->where('status', 'active');
        }

        return $query->count();
    }

    private function unreadNotificationCount(User $user): int
    {
        if (! class_exists(SystemNotification::class)) {
            return 0;
        }

        if (! Schema::hasTable('system_notifications')) {
            return 0;
        }

        $query = SystemNotification::query()
            ->where('user_id', $user->id)
            ->where(function ($subQuery) {
                $subQuery->where('is_read', false)
                    ->orWhereNull('read_at');
            });

        if (Schema::hasColumn('system_notifications', 'published_at')) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
        }

        if (Schema::hasColumn('system_notifications', 'expires_at')) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
        }

        return $query->count();
    }

    private function safeCount(string $modelClass): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        $model = new $modelClass;

        if (! Schema::hasTable($model->getTable())) {
            return 0;
        }

        return $modelClass::query()->count();
    }

    private function hafalanSummary(HafalanRecordSurah $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'student_name' => $record->student?->name,
            'surah_id' => $record->surah_id,
            'surah_name' => $record->surah?->name_latin,
            'ayah_start' => $record->ayah_start,
            'ayah_end' => $record->ayah_end,
            'submission_type' => $record->submission_type,
            'score' => $record->score,
            'status' => $record->status,
            'submitted_at' => optional($record->submitted_at)->format('Y-m-d'),
            'teacher_name' => $record->teacher?->user?->name,
        ];
    }

    private function murajaahSummary(MurajaahRecord $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'student_name' => $record->student?->name,
            'surah_id' => $record->surah_id,
            'surah_name' => $record->surah?->name_latin,
            'ayah_start' => $record->ayah_start,
            'ayah_end' => $record->ayah_end,
            'overall_score' => $record->overall_score,
            'status' => $record->status,
            'reviewed_at' => optional($record->reviewed_at)->format('Y-m-d'),
            'teacher_name' => $record->teacher?->user?->name,
        ];
    }

    private function targetSummary(HafalanTarget $target): array
    {
        return [
            'id' => $target->id,
            'student_id' => $target->student_id,
            'student_name' => $target->student?->name,
            'surah_id' => $target->surah_id,
            'surah_name' => $target->surah?->name_latin,
            'ayah' => $target->ayah,
            'ayah_range' => $target->ayah_range,
            'target_date' => optional($target->target_date)->format('Y-m-d'),
            'status' => $target->status,
            'completed_at' => optional($target->completed_at)->toISOString(),
            'teacher_name' => $target->teacher?->user?->name,
        ];
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        return in_array($user->role?->name, $roles, true);
    }
}
