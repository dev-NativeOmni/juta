<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tad:prune-api-tokens --days=7')
    ->dailyAt('02:00');

Schedule::command('notifications:generate')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Sinkronisasi otomatis target hafalan: tandai 'completed' jika sudah ada
// setoran hafalan lulus yang mencakup seluruh range ayat target.
Schedule::command('tad:sync-completed-targets')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Target hafalan otomatis (kelas 11 & 12): segarkan per hari supaya bulan baru/kalender
// yang berubah ikut terhitung; perubahan setoran sudah disinkronkan langsung saat disimpan.
Schedule::command('tad:sync-auto-targets')
    ->dailyAt('00:30')
    ->withoutOverlapping();

// Backup database harian pukul 03:00 pagi dan hapus backup lama
Schedule::command('tad:backup-database --prune')
    ->dailyAt('03:00')
    ->withoutOverlapping();
