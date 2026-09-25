<?php

namespace App\Http\Controllers;

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

    public function store(): RedirectResponse
    {
        $exitCode = Artisan::call('tad:backup-database', [
            '--prune' => true,
        ]);

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()
                ->route('database-backups.index')
                ->with('error', 'Backup database gagal. Detail: '.$output);
        }

        return redirect()
            ->route('database-backups.index')
            ->with('success', 'Backup database berhasil dibuat.');
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        return response()->download($path, basename($path));
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        File::delete($path);

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
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
