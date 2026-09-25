<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Console\Command;

class GenerateInternalNotifications extends Command
{
    protected $signature = 'notifications:generate {--user= : Generate notifications for a specific user ID}';

    protected $description = 'Generate internal TAD notifications for overdue targets and records that need attention.';

    public function handle(InternalNotificationService $notificationService): int
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = User::query()
                ->with([
                    'role',
                    'teacherProfile',
                    'parentProfile.students',
                    'studentProfile',
                ])
                ->find($userId);

            if (! $user) {
                $this->error('User tidak ditemukan.');

                return self::FAILURE;
            }

            $created = $notificationService->generateForUser($user);

            $this->info($created.' notifikasi dibuat untuk user ID '.$user->id.'.');

            return self::SUCCESS;
        }

        $created = $notificationService->generateForAllActiveUsers();

        $this->info($created.' notifikasi dibuat untuk seluruh user aktif.');

        return self::SUCCESS;
    }
}
