<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class StudentAlertNotificationService
{
    public function __construct(
        private readonly SystemNotificationService $notificationService
    ) {}

    public function generate(bool $dryRun = false): array
    {
        $studentsChecked = 0;
        $notificationsWritten = 0;

        Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(50, function ($students) use (&$studentsChecked, &$notificationsWritten, $dryRun) {
                foreach ($students as $student) {
                    $studentsChecked++;

                    $recipients = $this->recipientsForStudent($student);

                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    $actionUrl = $this->progressUrl($student);

                    $notificationsWritten += $this->handleOverdueTargets(
                        student: $student,
                        recipients: $recipients,
                        actionUrl: $actionUrl,
                        dryRun: $dryRun
                    );

                    $notificationsWritten += $this->handleHafalanAttention(
                        student: $student,
                        recipients: $recipients,
                        actionUrl: $actionUrl,
                        dryRun: $dryRun
                    );

                    $notificationsWritten += $this->handleMurajaahInactive(
                        student: $student,
                        recipients: $recipients,
                        actionUrl: $actionUrl,
                        dryRun: $dryRun
                    );

                    $notificationsWritten += $this->handleEmptyActiveTarget(
                        student: $student,
                        recipients: $recipients,
                        actionUrl: $actionUrl,
                        dryRun: $dryRun
                    );
                }
            });

        return [
            'students_checked' => $studentsChecked,
            'notifications_written' => $notificationsWritten,
        ];
    }

    private function handleOverdueTargets(
        Student $student,
        Collection $recipients,
        ?string $actionUrl,
        bool $dryRun
    ): int {
        $count = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'planned', 'in_progress'])
            ->whereDate('target_date', '<', today())
            ->count();

        if ($count <= 0) {
            return 0;
        }

        if ($dryRun) {
            return $recipients->count();
        }

        return $this->notificationService->notifyMany(
            users: $recipients,
            type: 'target_overdue',
            title: 'Target hafalan terlambat',
            message: "{$student->name} memiliki {$count} target hafalan yang melewati tanggal target. Prioritasnya adalah menuntaskan target lama sebelum membuat target baru.",
            priority: 'high',
            actionUrl: $actionUrl,
            student: $student,
            data: [
                'overdue_targets' => $count,
            ]
        );
    }

    private function handleHafalanAttention(
        Student $student,
        Collection $recipients,
        ?string $actionUrl,
        bool $dryRun
    ): int {
        $count = HafalanRecordSurah::query()
            ->whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
            ->whereIn('status', ['repeat', 'needs_improvement'])
            ->count();

        if ($count <= 0) {
            return 0;
        }

        if ($dryRun) {
            return $recipients->count();
        }

        return $this->notificationService->notifyMany(
            users: $recipients,
            type: 'hafalan_attention',
            title: 'Setoran hafalan perlu perhatian',
            message: "{$student->name} memiliki {$count} setoran hafalan yang perlu diulang atau ditingkatkan. Jangan kejar kuantitas sebelum kualitasnya stabil.",
            priority: 'high',
            actionUrl: $actionUrl,
            student: $student,
            data: [
                'attention_records' => $count,
            ]
        );
    }

    private function handleMurajaahInactive(
        Student $student,
        Collection $recipients,
        ?string $actionUrl,
        bool $dryRun
    ): int {
        $latestMurajaah = MurajaahRecord::query()
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest('id')
            ->first();

        $isInactive = ! $latestMurajaah || ! $latestMurajaah->reviewed_at || $latestMurajaah->reviewed_at->lt(now()->subDays(7));

        if (! $isInactive) {
            return 0;
        }

        if ($dryRun) {
            return $recipients->count();
        }

        $lastDate = $latestMurajaah?->reviewed_at
            ? $latestMurajaah->reviewed_at->format('d M Y')
            : 'belum pernah tercatat';

        return $this->notificationService->notifyMany(
            users: $recipients,
            type: 'murajaah_inactive',
            title: 'Murajaah belum aktif',
            message: "{$student->name} belum memiliki murajaah aktif dalam 7 hari terakhir. Murajaah terakhir: {$lastDate}.",
            priority: 'normal',
            actionUrl: $actionUrl,
            student: $student,
            data: [
                'last_murajaah_at' => $latestMurajaah?->reviewed_at?->toDateTimeString(),
            ]
        );
    }

    private function handleEmptyActiveTarget(
        Student $student,
        Collection $recipients,
        ?string $actionUrl,
        bool $dryRun
    ): int {
        $activeTargetCount = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'planned', 'in_progress'])
            ->count();

        if ($activeTargetCount > 0) {
            return 0;
        }

        if ($dryRun) {
            return $recipients->count();
        }

        return $this->notificationService->notifyMany(
            users: $recipients,
            type: 'target_empty',
            title: 'Belum ada target aktif',
            message: "{$student->name} belum memiliki target hafalan aktif. Tanpa target aktif, progres sulit dikontrol oleh guru dan orangtua.",
            priority: 'normal',
            actionUrl: $actionUrl,
            student: $student,
            data: [
                'active_targets' => 0,
            ]
        );
    }

    private function recipientsForStudent(Student $student): Collection
    {
        $users = collect();

        if ($student->user instanceof User) {
            $users->push($student->user);
        }

        if ($student->teacher?->user instanceof User) {
            $users->push($student->teacher->user);
        }

        foreach ($student->parents ?? collect() as $parent) {
            if ($parent->user instanceof User) {
                $users->push($parent->user);
            }
        }

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();
    }

    private function progressUrl(Student $student): ?string
    {
        if (Route::has('progress.show')) {
            return route('progress.show', $student);
        }

        if (Route::has('progress.index')) {
            return route('progress.index');
        }

        return null;
    }
}
