<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function index(): View
    {
        $backups = $this->backups();

        return view('database-backups.index', [
            'backups' => $backups,
            'backupPath' => (string) config('database_backup.path'),
            'retentionDays' => (int) config('database_backup.retention_days', 14),
            'latestBackup' => $backups[0] ?? null,
        ]);
    }

    public function store(AuditLogService $audit): RedirectResponse
    {
        Artisan::queue('tad:backup-database', [
            '--prune' => true,
        ]);

        $audit->logAction('backup_requested', 'Menjadwalkan backup database baru.');

        return redirect()
            ->route('database-backups.index')
            ->with('success', 'Backup database sedang diproses di latar belakang.');
    }

    public function download(string $filename, AuditLogService $audit): BinaryFileResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        $safeName = basename($path);

        $audit->logAction('backup_downloaded', "Mengunduh file backup database: {$safeName}", [
            'filename' => $safeName,
        ]);

        return response()->download($path, $safeName);
    }

    public function destroy(string $filename, AuditLogService $audit): RedirectResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        $safeName = basename($path);

        File::delete($path);

        $audit->logAction('backup_deleted', "Menghapus file backup database: {$safeName}", [
            'filename' => $safeName,
        ]);

        return redirect()
            ->route('database-backups.index')
            ->with('success', 'File backup berhasil dihapus.');
    }

    private function backups(): array
    {
        $backupDirectory = (string) config('database_backup.path');

        if (! File::isDirectory($backupDirectory)) {
            return [];
        }

        $files = collect(File::files($backupDirectory))
            ->filter(fn ($file) => $file->getExtension() === 'sql')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        return $files
            ->map(function ($file) {
                return [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            })
            ->all();
    }

    private function resolveBackupPath(string $filename): string
    {
        $safeFilename = basename($filename);

        return (string) config('database_backup.path').DIRECTORY_SEPARATOR.$safeFilename;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
