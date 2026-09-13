<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

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

    public function store(Request $request): RedirectResponse
    {
        // Dispatched to the queue instead of Artisan::call() so a large
        // mysqldump can't time out the HTTP request/PHP-FPM worker.
        Artisan::queue('ims:backup-database', [
            '--prune' => $request->boolean('prune', true),
        ]);

        $this->auditLog->logAction(
            action: 'backup_requested',
            description: ($request->user()?->name ?? 'System').' menjadwalkan backup database.',
        );

        return redirect()
            ->route('database-backups.index')
            ->with('success', 'Backup database sedang diproses di background. Muat ulang halaman ini beberapa saat lagi untuk melihat hasilnya.');
    }

    public function download(Request $request, string $filename): BinaryFileResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        $this->auditLog->logAction(
            action: 'backup_downloaded',
            description: ($request->user()?->name ?? 'System').' mengunduh file backup database.',
            context: ['filename' => basename($path)],
        );

        return response()->download($path, basename($path));
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        File::delete($path);

        $this->auditLog->logAction(
            action: 'backup_deleted',
            description: ($request->user()?->name ?? 'System').' menghapus file backup database.',
            context: ['filename' => basename($path)],
        );

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
