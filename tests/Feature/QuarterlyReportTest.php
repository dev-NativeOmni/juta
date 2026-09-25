<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuarterlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_quarterly_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.quarterly'));
        $response->assertRedirect('/login');
    }

    public function test_quarterly_report_accessible_by_super_admin_and_admin(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        $responseSuper = $this->actingAs($superAdmin)->get(route('reports.quarterly'));
        $responseSuper->assertStatus(200);
        $responseSuper->assertViewIs('reports.quarterly');
        $responseSuper->assertSee('Laporan Perkembangan Triwulan');

        $responseAdmin = $this->actingAs($admin)->get(route('reports.quarterly'));
        $responseAdmin->assertStatus(200);
    }

    public function test_quarterly_report_forbidden_for_other_roles(): void
    {
        $student = User::where('username', 'santri')->first();

        $parentRole = Role::where('name', 'parent')->first();
        $parent = User::factory()->create([
            'role_id' => $parentRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)->get(route('reports.quarterly'))->assertStatus(403);
        $this->actingAs($parent)->get(route('reports.quarterly'))->assertStatus(403);
    }

    public function test_teacher_sees_the_same_preview_page_with_personal_downloads(): void
    {
        $teacher = User::where('username', 'guru')->first();

        $response = $this->actingAs($teacher)->get(route('reports.quarterly'));
        $response->assertStatus(200);
        $response->assertViewIs('reports.quarterly');
        $response->assertViewHas('isTeacherView', true);
        $response->assertSee('Laporan Perkembangan Triwulan');
        $response->assertSee(route('reports.quarterly.export.mine', ['program' => 'reguler']), false);
        $response->assertDontSee('Eksklusif Admin');
    }
}
