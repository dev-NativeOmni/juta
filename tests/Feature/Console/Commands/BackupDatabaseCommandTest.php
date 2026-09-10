<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    public function test_it_fails_if_default_connection_is_not_mysql()
    {
        config(['database.default' => 'sqlite']);

        $this->artisan('ims:backup-database')
            ->expectsOutputToContain('Backup ini hanya mendukung koneksi mysql')
            ->assertExitCode(1);
    }

    public function test_it_fails_if_database_name_is_empty()
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => '']);

        $this->artisan('ims:backup-database')
            ->expectsOutputToContain('Nama database tidak ditemukan di konfigurasi')
            ->assertExitCode(1);
    }

    public function test_it_successfully_creates_a_backup()
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'test_db']);
        config(['database.connections.mysql.username' => 'test_user']);
        config(['database.connections.mysql.password' => 'test_pass']);

        $backupDir = storage_path('app/testing_backups');
        config(['database_backup.path' => $backupDir]);
        config(['database_backup.mysqldump_path' => base_path('tests/dummy_mysqldump.sh')]);

        File::cleanDirectory($backupDir);

        $this->artisan('ims:backup-database')
            ->expectsOutputToContain('Memulai backup database...')
            ->expectsOutputToContain('Backup berhasil dibuat.')
            ->assertExitCode(0);

        $files = File::files($backupDir);
        $this->assertCount(1, $files);
        $this->assertStringContainsString('test_db', $files[0]->getFilename());
        $this->assertEquals("DUMMY SQL CONTENT\n", File::get($files[0]));

        File::cleanDirectory($backupDir);
    }

    public function test_it_handles_process_failure()
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'test_db']);
        config(['database.connections.mysql.username' => 'fail']);

        $backupDir = storage_path('app/testing_backups');
        config(['database_backup.path' => $backupDir]);
        config(['database_backup.mysqldump_path' => base_path('tests/dummy_mysqldump.sh')]);

        File::cleanDirectory($backupDir);

        $this->artisan('ims:backup-database')
            ->expectsOutputToContain('Backup gagal.')
            ->assertExitCode(1);

        $this->assertCount(0, File::files($backupDir));
    }

    public function test_it_fails_if_backup_file_is_empty()
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'test_db']);
        config(['database.connections.mysql.username' => 'empty']);

        $backupDir = storage_path('app/testing_backups');
        config(['database_backup.path' => $backupDir]);
        config(['database_backup.mysqldump_path' => base_path('tests/dummy_mysqldump.sh')]);

        File::cleanDirectory($backupDir);

        $this->artisan('ims:backup-database')
            ->expectsOutputToContain('Backup gagal. File backup kosong atau tidak terbentuk.')
            ->assertExitCode(1);

        $this->assertCount(0, File::files($backupDir));
    }

    public function test_it_prunes_old_backups()
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'test_db']);
        config(['database.connections.mysql.username' => 'test_user']);

        $backupDir = storage_path('app/testing_backups');
        config(['database_backup.path' => $backupDir]);
        config(['database_backup.mysqldump_path' => base_path('tests/dummy_mysqldump.sh')]);
        config(['database_backup.retention_days' => 14]);

        File::cleanDirectory($backupDir);

        // Create an old file
        $oldFile = $backupDir . '/old_backup.sql';
        File::put($oldFile, 'OLD CONTENT');
        // Set modify time to 15 days ago
        touch($oldFile, now()->subDays(15)->timestamp);

        // Create a recent file
        $recentFile = $backupDir . '/recent_backup.sql';
        File::put($recentFile, 'RECENT CONTENT');
        // Set modify time to 5 days ago
        touch($recentFile, now()->subDays(5)->timestamp);

        // Create a non-sql file that should not be touched
        $otherFile = $backupDir . '/old_file.txt';
        File::put($otherFile, 'TEXT CONTENT');
        touch($otherFile, now()->subDays(15)->timestamp);

        $this->artisan('ims:backup-database', ['--prune' => true])
            ->expectsOutputToContain('Backup lama dihapus: 1 file.')
            ->assertExitCode(0);

        $files = collect(File::files($backupDir))->map->getFilename()->toArray();

        // Should have recent, other, and the newly created one (total 3)
        $this->assertCount(3, $files);
        $this->assertNotContains('old_backup.sql', $files);
        $this->assertContains('recent_backup.sql', $files);
        $this->assertContains('old_file.txt', $files);

        File::cleanDirectory($backupDir);
    }

    protected function tearDown(): void
    {
        $backupDir = storage_path('app/testing_backups');
        if (File::exists($backupDir)) {
            File::cleanDirectory($backupDir);
        }

        parent::tearDown();
    }
}
