<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InternalNotificationService
{
    public function generateForAllActiveUsers(): int
    {
        $totalCreated = 0;

        User::query()
            ->with([
                'role',
                'teacherProfile',
                'parentProfile.students',
                'studentProfile',
            ])
            ->where('status', 'active')
            ->chunkById(100, function ($users) use (&$totalCreated) {
                foreach ($users as $user) {
                    $totalCreated += $this->generateForUser($user);
                }
            });

        return $totalCreated;
    }

    public function generateForUser(User $user): int
    {
        $user->loadMissing([
            'role',
            'teacherProfile',
            'parentProfile.students',
            'studentProfile',
        ]);

        $created = 0;

        $created += $this->generateOverdueTargetNotifications($user);
        $created += $this->generateHafalanAttentionNotifications($user);
        $created += $this->generateMurajaahAttentionNotifications($user);

        return $created;
    }

    private function generateOverdueTargetNotifications(User $user): int
    {
        $created = 0;

        $this->visibleTargetQuery($user)
            ->with([
                'student.classRoom',
                'teacher.user',
                'surah',
            ])
            ->where('status', 'active')
            ->whereDate('target_date', '<', today())
            ->orderBy('target_date')
            ->get()
            ->each(function (HafalanTarget $target) use ($user, &$created) {
                $studentName = $target->student?->name ?? 'Murid';
                $surahName = $target->surah?->name_latin ?? 'Surah';

                $created += $this->createUniqueNotification(
                    user: $user,
                    type: 'target_overdue',
                    severity: 'warning',
                    title: 'Target hafalan terlambat',
                    message: $studentName.' memiliki target '.$surahName.' ayat '.$target->ayah_range.' yang melewati tanggal target '.$target->target_date?->format('d M Y').'.',
                    sourceType: HafalanTarget::class,
                    sourceId: $target->id,
                    actionUrl: $this->targetActionUrl($user, $target),
                    code: 'target-overdue'
                );
            });

        return $created;
    }

    private function generateHafalanAttentionNotifications(User $user): int
    {
        $created = 0;

        $this->visibleHafalanSurahQuery($user)
            ->with([
                'hafalanRecord.student.classRoom',
                'hafalanRecord.teacher.user',
                'surah',
            ])
            ->whereIn('hafalan_record_surahs.status', [
                'repeat',
                'needs_improvement',
            ])
            ->orderByDesc('hafalan_records.submitted_at')
            ->limit(100)
            ->get()
            ->each(function (HafalanRecordSurah $record) use ($user, &$created) {
                $record->applyHeaderOverlay();
                $studentName = $record->student?->name ?? 'Murid';
                $surahName = $record->surah?->name_latin ?? 'Surah';

                $created += $this->createUniqueNotification(
                    user: $user,
                    type: 'hafalan_attention',
                    severity: 'danger',
                    title: 'Hafalan perlu tindak lanjut',
                    message: $studentName.' memiliki setoran '.$surahName.' ayat '.$record->ayah_range.' dengan status '.$record->status_label.'.',
                    sourceType: HafalanRecordSurah::class,
                    sourceId: $record->id,
                    actionUrl: $this->recordActionUrl($user, 'hafalan-records', $record->hafalan_record_id),
                    code: 'hafalan-attention'
                );
            });

        return $created;
    }

    private function generateMurajaahAttentionNotifications(User $user): int
    {
        $created = 0;

        $this->visibleMurajaahRecordQuery($user)
            ->with([
                'student.classRoom',
                'teacher.user',
                'surah',
            ])
            ->whereIn('status', [
                'repeat',
                'needs_improvement',
            ])
            ->latest('reviewed_at')
            ->limit(100)
            ->get()
            ->each(function (MurajaahRecord $record) use ($user, &$created) {
                $studentName = $record->student?->name ?? 'Murid';
                $surahName = $record->surah?->name_latin ?? 'Surah';

                $created += $this->createUniqueNotification(
                    user: $user,
                    type: 'murajaah_attention',
                    severity: 'danger',
                    title: 'Murajaah perlu tindak lanjut',
                    message: $studentName.' memiliki murajaah '.$surahName.' ayat '.$record->ayah_range.' dengan status '.$record->status_label.'.',
                    sourceType: MurajaahRecord::class,
                    sourceId: $record->id,
                    actionUrl: $this->recordActionUrl($user, 'murajaah-records', $record->id),
                    code: 'murajaah-attention'
                );
            });

        return $created;
    }

    private function visibleTargetQuery(User $user): Builder
    {
        $query = HafalanTarget::query();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->where('teacher_id', $user->teacherProfile?->id ?? 0);
        }

        $studentIds = $this->visibleStudentIds($user);

        return $studentIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('student_id', $studentIds);
    }

    private function visibleHafalanSurahQuery(User $user): Builder
    {
        $query = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at');

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->where('hafalan_records.teacher_id', $user->teacherProfile?->id ?? 0);
        }

        $studentIds = $this->visibleStudentIds($user);

        return $studentIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('hafalan_records.student_id', $studentIds);
    }

    private function visibleMurajaahRecordQuery(User $user): Builder
    {
        $query = MurajaahRecord::query();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->where('teacher_id', $user->teacherProfile?->id ?? 0);
        }

        $studentIds = $this->visibleStudentIds($user);

        return $studentIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('student_id', $studentIds);
    }

    private function visibleStudentIds(User $user): Collection
    {
        if ($user->hasRole('parent')) {
            $parent = $user->parentProfile;

            if (! $parent) {
                return collect();
            }

            return $parent->students()
                ->pluck('students.id');
        }

        if ($user->hasRole('student')) {
            $studentId = $user->studentProfile?->id;

            return $studentId ? collect([$studentId]) : collect();
        }

        return collect();
    }

    private function createUniqueNotification(
        User $user,
        string $type,
        string $severity,
        string $title,
        string $message,
        string $sourceType,
        int $sourceId,
        string $actionUrl,
        string $code
    ): int {
        $uniqueHash = hash('sha256', implode('|', [
            $user->id,
            $type,
            $sourceType,
            $sourceId,
            $code,
        ]));

        $notification = SystemNotification::query()->firstOrCreate(
            [
                'unique_hash' => $uniqueHash,
            ],
            [
                'user_id' => $user->id,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'action_url' => $actionUrl,
            ]
        );

        return $notification->wasRecentlyCreated ? 1 : 0;
    }

    private function targetActionUrl(User $user, HafalanTarget $target): string
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            return '/hafalan-targets/'.$target->id;
        }

        return '/dashboard';
    }

    private function recordActionUrl(User $user, string $resourcePath, int $recordId): string
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            return '/'.$resourcePath.'/'.$recordId;
        }

        return '/dashboard';
    }
}
