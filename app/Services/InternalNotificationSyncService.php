<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\InternalNotification;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class InternalNotificationSyncService
{
    public function syncAll(): array
    {
        return [
            'target_overdue' => $this->syncOverdueTargets(),
            'hafalan_follow_up' => $this->syncHafalanFollowUps(),
            'murajaah_follow_up' => $this->syncMurajaahFollowUps(),
        ];
    }

    public function syncOverdueTargets(): int
    {
        $count = 0;

        HafalanTarget::query()
            ->with([
                'student.user',
                'student.parents.user',
                'student.teacher.user',
                'teacher.user',
                'surah',
            ])
            ->where('status', 'active')
            ->whereDate('target_date', '<', today())
            ->orderBy('target_date')
            ->chunkById(100, function ($targets) use (&$count) {
                foreach ($targets as $target) {
                    $studentName = $target->student?->name ?? 'Murid';
                    $surahName = $target->surah?->name_latin ?? 'Surah';
                    $ayahRange = $target->ayah_range;
                    $targetDate = $target->target_date?->format('d M Y') ?? '-';

                    $users = $this->usersForStudentAndTeacher(
                        $target->student,
                        $target->teacher
                    );

                    foreach ($users as $user) {
                        $count += $this->upsertNotification(
                            user: $user,
                            type: 'target_overdue',
                            title: 'Target hafalan terlambat',
                            message: "{$studentName} memiliki target {$surahName} ayat {$ayahRange} yang melewati tanggal {$targetDate}.",
                            actionUrl: $this->actionUrlForUser($user, 'target', $target->id),
                            priority: 'high',
                            source: $target
                        );
                    }
                }
            });

        return $count;
    }

    public function syncHafalanFollowUps(): int
    {
        $count = 0;

        HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->with([
                'hafalanRecord.student.user',
                'hafalanRecord.student.parents.user',
                'hafalanRecord.student.teacher.user',
                'hafalanRecord.teacher.user',
                'surah',
            ])
            ->whereIn('hafalan_record_surahs.status', ['repeat', 'needs_improvement'])
            ->orderByDesc('hafalan_records.submitted_at')
            ->chunkById(100, function ($records) use (&$count) {
                foreach ($records as $record) {
                    $record->applyHeaderOverlay();
                    $studentName = $record->student?->name ?? 'Murid';
                    $surahName = $record->surah?->name_latin ?? 'Surah';
                    $ayahRange = $record->ayah_range ?? ($record->ayah_start.' - '.$record->ayah_end);
                    $statusLabel = $record->status_label ?? $this->statusLabel($record->status);

                    $users = $this->usersForStudentAndTeacher(
                        $record->student,
                        $record->teacher
                    );

                    foreach ($users as $user) {
                        $count += $this->upsertNotification(
                            user: $user,
                            type: 'hafalan_follow_up',
                            title: 'Hafalan perlu follow-up',
                            message: "Setoran hafalan {$studentName} pada {$surahName} ayat {$ayahRange} berstatus {$statusLabel}.",
                            actionUrl: $this->actionUrlForUser($user, 'hafalan', $record->hafalan_record_id),
                            priority: 'normal',
                            source: $record
                        );
                    }
                }
            }, 'hafalan_record_surahs.id', 'id');

        return $count;
    }

    public function syncMurajaahFollowUps(): int
    {
        $count = 0;

        MurajaahRecord::query()
            ->with([
                'student.user',
                'student.parents.user',
                'student.teacher.user',
                'teacher.user',
                'surah',
            ])
            ->whereIn('status', ['repeat', 'needs_improvement'])
            ->orderByDesc('reviewed_at')
            ->chunkById(100, function ($records) use (&$count) {
                foreach ($records as $record) {
                    $studentName = $record->student?->name ?? 'Murid';
                    $surahName = $record->surah?->name_latin ?? 'Surah';
                    $ayahRange = $record->ayah_range ?? ($record->ayah_start.' - '.$record->ayah_end);
                    $statusLabel = $record->status_label ?? $this->statusLabel($record->status);

                    $users = $this->usersForStudentAndTeacher(
                        $record->student,
                        $record->teacher
                    );

                    foreach ($users as $user) {
                        $count += $this->upsertNotification(
                            user: $user,
                            type: 'murajaah_follow_up',
                            title: 'Murajaah perlu follow-up',
                            message: "Murajaah {$studentName} pada {$surahName} ayat {$ayahRange} berstatus {$statusLabel}.",
                            actionUrl: $this->actionUrlForUser($user, 'murajaah', $record->id),
                            priority: 'normal',
                            source: $record
                        );
                    }
                }
            });

        return $count;
    }

    private function usersForStudentAndTeacher(?Student $student, ?TeacherProfile $teacher = null): Collection
    {
        $users = collect();

        if (! $student) {
            return $users;
        }

        $student->loadMissing([
            'user',
            'parents.user',
            'teacher.user',
        ]);

        if ($student->user) {
            $users->push($student->user);
        }

        if ($teacher?->user) {
            $users->push($teacher->user);
        } elseif ($student->teacher?->user) {
            $users->push($student->teacher->user);
        }

        foreach ($student->parents as $parent) {
            if ($parent->user) {
                $users->push($parent->user);
            }
        }

        return $users
            ->filter(fn ($user) => $user instanceof User && $user->isActive())
            ->unique('id')
            ->values();
    }

    private function upsertNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl,
        string $priority,
        Model $source
    ): int {
        InternalNotification::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
            ],
            [
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'priority' => $priority,
            ]
        );

        return 1;
    }

    private function actionUrlForUser(User $user, string $context, int $id): string
    {
        if (! $user->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            return url('/dashboard');
        }

        return match ($context) {
            'target' => url("/hafalan-targets/{$id}/edit"),
            'hafalan' => url("/hafalan-records/{$id}"),
            'murajaah' => url("/murajaah-records/{$id}"),
            default => url('/dashboard'),
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'passed' => 'Lulus',
            'repeat' => 'Ulang',
            'needs_improvement' => 'Perlu Perbaikan',
            default => '-',
        };
    }
}
