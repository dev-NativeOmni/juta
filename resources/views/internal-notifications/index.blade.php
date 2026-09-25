<?php

namespace App\Console\Commands;

use App\Services\InternalNotificationSyncService;
use Illuminate\Console\Command;

class SyncInternalNotifications extends Command
{
    protected $signature = 'ims:sync-notifications';

    protected $description = 'Sinkronkan notifikasi internal TAD untuk target terlambat, hafalan follow-up, dan murajaah follow-up.';

    public function handle(InternalNotificationSyncService $service): int
    {
        $this->info('Mulai sinkronisasi notifikasi internal...');

        $result = $service->syncAll();

        $this->table(
            ['Jenis', 'Jumlah Diproses'],
            [
                ['Target terlambat', $result['target_overdue']],
                ['Follow-up hafalan', $result['hafalan_follow_up']],
                ['Follow-up murajaah', $result['murajaah_follow_up']],
            ]
        );

        $this->info('Sinkronisasi notifikasi selesai.');

        return self::SUCCESS;
    }
}