<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'tad:backup-database {--prune : Hapus backup lama setelah backup berhasil}';

    protected $aliases = ['ims:backup-database'];

    protected $description = 'Membuat backup database MySQL TAD dalam format SQL.';

    public function handle(): int
    {
        $defaultConnection = config('database.default');

        if ($defaultConnection !== 'mysql') {
            $this->error('Backup ini hanya mendukung koneksi mysql. Koneksi aktif sekarang: '.$defaultConnection);

            return self::FAILURE;
        }

        $connection = config('database.connections.mysql');

        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            $this->error('Nama database tidak ditemukan di konfigurasi.');

            return self::FAILURE;
        }

        $backupDirectory = (string) config('database_backup.path');

        File::ensureDirectoryExists($backupDirectory);

        $filename = now()->format('Y-m-d_His').'_'.$this->safeFilename($database).'.sql';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;

        $command = $this->buildCommand($connection, $database, $backupPath);

        $this->info('Memulai backup database...');
        $this->line('Database: '.$database);
        $this->line('Target: '.$backupPath);

        $process = new Process($command);
        $process->setTimeout((int) config('database_backup.timeout', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            if (File::exists($backupPath)) {
                File::delete($backupPath);
            }

            $this->error('Backup gagal.');
            $this->line($process->getErrorOutput() ?: $process->getOutput());

            return self::FAILURE;
        }

        if (! File::exists($backupPath) || File::size($backupPath) < 1) {
            if (File::exists($backupPath)) {
                File::delete($backupPath);
            }

            $this->error('Backup gagal. File backup kosong atau tidak terbentuk.');

            return self::FAILURE;
        }

        if ($this->option('prune')) {
            $deleted = $this->pruneOldBackups();

            if ($deleted > 0) {
                $this->info("Backup lama dihapus: {$deleted} file.");
            }
        }

        $this->info('Backup berhasil dibuat.');
        $this->line('File: '.$backupPath);
        $this->line('Ukuran: '.$this->formatBytes(File::size($backupPath)));

        return self::SUCCESS;
    }

    private function buildCommand(array $connection, string $database, string $backupPath): array
    {
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');

        $command = [
            (string) config('database_backup.mysqldump_path', 'mysqldump'),
            '--host='.$host,
            '--port='.$port,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--databases',
            $database,
            '--result-file='.$backupPath,
        ];

        if ($username !== '') {
            $command[] = '--user='.$username;
        }

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        return $command;
    }

    private function pruneOldBackups(): int
    {
        $backupDirectory = (string) config('database_backup.path');
        $retentionDays = (int) config('database_backup.retention_days', 14);

        if ($retentionDays <= 0 || ! File::isDirectory($backupDirectory)) {
            return 0;
        }

        $deleted = 0;
        $cutoffTimestamp = now()->subDays($retentionDays)->timestamp;

        foreach (File::files($backupDirectory) as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }

            if ($file->getMTime() >= $cutoffTimestamp) {
                continue;
            }

            File::delete($file->getPathname());
            $deleted++;
        }

        return $deleted;
    }

    private function safeFilename(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $value);

        return $clean ?: 'database';
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
