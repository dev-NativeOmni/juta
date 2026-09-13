<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseBackupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->teacher = User::factory()->create(['role_id' => $teacherRole->id, 'status' => 'active']);

        $this->backupDir = storage_path('app/testing_backups_controller');
        config(['database_backup.path' => $this->backupDir]);
        File::ensureDirectoryExists($this->backupDir);
        File::cleanDirectory($this->backupDir);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->backupDir)) {
            File::cleanDirectory($this->backupDir);
        }

        parent::tearDown();
    }

    private function putFakeBackupFile(string $filename = 'backup_test.sql'): string
    {
        $path = $this->backupDir.DIRECTORY_SEPARATOR.$filename;
        File::put($path, 'DUMMY SQL CONTENT');

        return $path;
    }

    #[Test]
    public function guest_cannot_access_backup_routes(): void
    {
        $this->putFakeBackupFile();

        $this->get(route('database-backups.index'))->assertRedirect(route('login'));
        $this->get(route('database-backups.download', 'backup_test.sql'))->assertRedirect(route('login'));
        $this->delete(route('database-backups.destroy', 'backup_test.sql'))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_cannot_access_backup_routes(): void
    {
        $this->putFakeBackupFile();

        $this->actingAs($this->teacher)->get(route('database-backups.index'))->assertStatus(403);
        $this->actingAs($this->teacher)->get(route('database-backups.download', 'backup_test.sql'))->assertStatus(403);
        $this->actingAs($this->teacher)->delete(route('database-backups.destroy', 'backup_test.sql'))->assertStatus(403);
    }

    #[Test]
    public function admin_can_download_an_existing_backup_and_it_is_audit_logged(): void
    {
        $this->putFakeBackupFile('backup_test.sql');

        $response = $this->actingAs($this->admin)
            ->get(route('database-backups.download', 'backup_test.sql'));

        $response->assertOk();
        $response->assertDownload('backup_test.sql');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'backup_downloaded',
        ]);
    }

    #[Test]
    public function downloading_a_missing_backup_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('database-backups.download', 'does-not-exist.sql'))
            ->assertStatus(404);
    }

    #[Test]
    public function download_cannot_escape_the_backup_directory_via_path_traversal(): void
    {
        // basename() in the controller must strip any directory traversal attempt,
        // so a crafted filename can never reach a file outside the backup directory.
        File::put(storage_path('app/outside-secret.sql'), 'SHOULD NOT BE SERVED');

        $url = route('database-backups.download', ['filename' => '../outside-secret.sql']);

        $this->actingAs($this->admin)
            ->get($url)
            ->assertStatus(404);

        File::delete(storage_path('app/outside-secret.sql'));
    }

    #[Test]
    public function admin_can_delete_a_backup_and_it_is_audit_logged(): void
    {
        $path = $this->putFakeBackupFile('to_delete.sql');

        $response = $this->actingAs($this->admin)
            ->delete(route('database-backups.destroy', 'to_delete.sql'));

        $response->assertRedirect(route('database-backups.index'));
        $this->assertFileDoesNotExist($path);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'backup_deleted',
        ]);
    }

    #[Test]
    public function deleting_a_missing_backup_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('database-backups.destroy', 'does-not-exist.sql'))
            ->assertStatus(404);
    }

    #[Test]
    public function admin_triggering_a_backup_queues_the_job_instead_of_blocking_the_request(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)->post(route('database-backups.store'));

        $response->assertRedirect(route('database-backups.index'));
        Queue::assertPushed(QueuedCommand::class);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'backup_requested',
        ]);
    }
}
