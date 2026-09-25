<?php

namespace App\Console\Commands;

use App\Services\HafalanTargetAutoCompletionService;
use Illuminate\Console\Command;

class SyncCompletedHafalanTargets extends Command
{
    protected $signature = 'tad:sync-completed-targets {--dry-run : Tampilkan jumlah target yang cocok tanpa mengubah database}';

    protected $aliases = ['ims:sync-completed-targets'];

    protected $description = 'Sinkronkan target hafalan aktif menjadi selesai jika sudah ada setoran hafalan lulus yang mencakup target tersebut.';

    public function handle(HafalanTargetAutoCompletionService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Mode dry-run aktif. Database tidak akan diubah.'
            : 'Mulai sinkronisasi target hafalan...'
        );

        $matchedTargets = $service->syncExistingTargets($dryRun);

        if ($dryRun) {
            $this->info("Ditemukan {$matchedTargets} target aktif yang bisa ditandai selesai.");
        } else {
            $this->info("Selesai. {$matchedTargets} target hafalan berhasil disinkronkan menjadi completed.");
        }

        return self::SUCCESS;
    }
}
