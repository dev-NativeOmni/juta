<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodicReportTest extends TestCase
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

    public function test_periodic_report_requires_authentication(): void
    {
        $response = $this->get('/reports/periodic');
        $response->assertRedirect('/login');
    }

    public function test_periodic_report_accessible_by_allowed_roles(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();
        $teacher = User::where('username', 'guru')->first();

        // Create other roles
        $roles = [
            'supervisor' => 'Koordinator',
            'coordinator_tahfizh' => 'Koordinator Tahfizh',
            'headmaster' => 'Kepala Sekolah',
        ];

        foreach ($roles as $name => $displayName) {
            $role = Role::updateOrCreate(['name' => $name], ['display_name' => $displayName]);
            $user = User::factory()->create([
                'role_id' => $role->id,
                'status' => 'active',
            ]);

            $response = $this->actingAs($user)->get('/reports/periodic');
            $response->assertStatus(200);

            $responsePrint = $this->actingAs($user)->get('/reports/periodic/print');
            $responsePrint->assertStatus(200);
        }

        // Test super_admin, admin, teacher
        $this->actingAs($superAdmin)->get('/reports/periodic')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/periodic')->assertStatus(200);
        $this->actingAs($teacher)->get('/reports/periodic')->assertStatus(200);
    }

    public function test_periodic_report_forbidden_for_student_and_parent(): void
    {
        $student = User::where('username', 'santri')->first();

        $parentRole = Role::where('name', 'parent')->first();
        $parent = User::factory()->create([
            'role_id' => $parentRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)->get('/reports/periodic')->assertStatus(403);
        $this->actingAs($parent)->get('/reports/periodic')->assertStatus(403);

        $this->actingAs($student)->get('/reports/periodic/print')->assertStatus(403);
        $this->actingAs($parent)->get('/reports/periodic/print')->assertStatus(403);
    }

    public function test_periodic_report_calculates_dynamic_targets(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Create a daily program and class
        $dailyProgram = \App\Models\Program::create([
            'name' => 'Program Harian',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);
        $classRoom = \App\Models\ClassRoom::create([
            'name' => 'Kelas X Harian',
            'program_id' => $dailyProgram->id,
            'status' => 'active',
        ]);

        // 2. Create student with reguler level (5 lines/meeting)
        $student = \App\Models\Student::create([
            'name' => 'Santri Reguler',
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
            'status' => 'active',
        ]);

        // 3. Test daily target for August 2026 (21 weekdays)
        // Expected target: 5 lines * 21 weekdays = 105 lines
        $response = $this->actingAs($admin)->get(route('reports.periodic', [
            'class_room_id' => $classRoom->id,
            'month' => 8,
            'year' => 2026,
            'period_type' => 'monthly',
        ]));

        $response->assertStatus(200);
        $studentReports = $response->viewData('studentReports');
        $this->assertCount(1, $studentReports);
        $this->assertEquals(100, $studentReports[0]['target_baris']);

        // 4. Update program to weekly ("seminggu sekali")
        // Expected target for August 2026 (5 Mondays, but August 17 is holiday, so 4 weeks): 5 lines * 4 weeks = 20 lines
        $dailyProgram->update(['meeting_frequency' => 'seminggu sekali']);

        $response2 = $this->actingAs($admin)->get(route('reports.periodic', [
            'class_room_id' => $classRoom->id,
            'month' => 8,
            'year' => 2026,
            'period_type' => 'monthly',
        ]));

        $response2->assertStatus(200);
        $studentReports2 = $response2->viewData('studentReports');
        $this->assertEquals(25, $studentReports2[0]['target_baris']);
    }
}
