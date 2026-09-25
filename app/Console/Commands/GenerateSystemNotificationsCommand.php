<?php

namespace App\Console\Commands;

use App\Services\StudentAlertNotificationService;
use Illuminate\Console\Command;

class GenerateSystemNotificationsCommand extends Command
{
    protected $signature = 'tad:generate-system-notifications {--dry-run : Hitung potensi notifikasi tanpa menyimpan data}';

    protected $aliases = ['ims:generate-system-notifications'];

    protected $description = 'Generate notifikasi internal TAD berdasarkan target, hafalan, dan murajaah santri.';

    public function handle(StudentAlertNotificationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $result = $service->generate($dryRun);

        $this->info('Generate notifikasi selesai.');
        $this->line('Mode: '.($dryRun ? 'dry-run' : 'write'));
        $this->line('Santri dicek: '.$result['students_checked']);
        $this->line('Notifikasi '.($dryRun ? 'terdeteksi' : 'ditulis').': '.$result['notifications_written']);

        return self::SUCCESS;
    }
}
