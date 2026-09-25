<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Services\AutoHafalanTargetService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAutoHafalanTargets extends Command
{
    protected $signature = 'tad:sync-auto-targets {--date= : Tanggal (YYYY-MM-DD) di dalam term yang disinkronkan, default hari ini}';

    protected $description = 'Buat/perbarui target hafalan otomatis per bulan untuk kelas 11 & 12 dari setoran pertama term dan jumlah pertemuan x level.';

    public function handle(AutoHafalanTargetService $service): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->reject(fn (ClassRoom $classRoom) => $classRoom->isGradeTen());

        foreach ($classRooms as $classRoom) {
            $service->syncClass($classRoom, $date);
            $this->line("Sinkron: {$classRoom->name}");
        }

        $this->info("Selesai. {$classRooms->count()} kelas disinkronkan untuk term yang memuat {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
