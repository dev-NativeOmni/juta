<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->teacher = User::factory()->create(['role_id' => $teacherRole->id, 'status' => 'active']);
    }

    #[Test]
    public function guest_is_redirected_from_audit_log_index(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_cannot_view_audit_log_index(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('audit-logs.index'))
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_audit_log_index(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'action' => 'created',
            'auditable_label' => 'Program Tahfizh Reguler',
            'description' => 'Admin membuat Program Tahfizh Reguler.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.index'))
            ->assertStatus(200)
            ->assertSee('Program Tahfizh Reguler')
            ->assertSee($this->admin->name);
    }

    #[Test]
    public function admin_can_filter_audit_log_by_action(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'action' => 'created',
            'auditable_label' => 'Entri Dibuat XYZ',
            'description' => 'Entri dibuat oleh admin.',
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'action' => 'deleted',
            'auditable_label' => 'Entri Dihapus ABC',
            'description' => 'Entri dihapus oleh admin.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('audit-logs.index', ['event' => 'deleted']));

        $response->assertStatus(200)
            ->assertSee('Entri Dihapus ABC');

        // The label dropdown lists every label regardless of the active
        // filter, so assert on the filtered result set itself rather than
        // on raw page text for the row that should be excluded.
        $response->assertViewHas('auditLogs', function ($auditLogs) {
            return $auditLogs->count() === 1
                && $auditLogs->first()->auditable_label === 'Entri Dihapus ABC';
        });
    }

    #[Test]
    public function admin_can_view_single_audit_log_entry(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'action' => 'updated',
            'auditable_label' => 'Detail Entri Spesifik',
            'description' => 'Detail entri audit log yang spesifik.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.show', $log))
            ->assertStatus(200)
            ->assertSee('Detail Entri Spesifik');
    }
}
