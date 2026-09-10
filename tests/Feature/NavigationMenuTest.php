<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function super_admin_can_see_all_menus(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('programs.index'));
        $response->assertSee(route('class-rooms.index'));
        $response->assertSee(route('teachers.index'));
        $response->assertSee(route('parents.index'));
        $response->assertSee(route('students.index'));
        $response->assertSee(route('hafalan-records.index'));
        $response->assertSee(route('spreadsheet-input.index'));
        $response->assertSee(route('hafalan-targets.index'));
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('digital-reports.index'));
        $response->assertSee(route('reports.teachers'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('users.index'));
        $response->assertSee(route('system-notifications.index'));
        $response->assertSee(route('audit-logs.index'));
        $response->assertSee(route('settings.index'));
    }

    #[Test]
    public function admin_can_see_allowed_menus_but_not_super_admin_specific_menus(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // Should see
        $response->assertSee(route('programs.index'));
        $response->assertSee(route('class-rooms.index'));
        $response->assertSee(route('teachers.index'));
        $response->assertSee(route('parents.index'));
        $response->assertSee(route('students.index'));
        $response->assertSee(route('hafalan-records.index'));
        $response->assertSee(route('spreadsheet-input.index'));
        $response->assertSee(route('hafalan-targets.index'));
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('digital-reports.index'));
        $response->assertSee(route('reports.teachers'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
        $response->assertSee(route('audit-logs.index'));

        // Should NOT see
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('settings.index'));
    }

    #[Test]
    public function teacher_menu_visibility(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('teacher.dashboard'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('hafalan-records.index'));
        $response->assertSee(route('spreadsheet-input.index'));
        $response->assertSee(route('hafalan-targets.index'));
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('digital-reports.index'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function parent_menu_visibility(): void
    {
        $response = $this->actingAs($this->parentUser)->get(route('parent.dashboard'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('hafalan-records.index'));
        $response->assertDontSee(route('murajaah-records.index'));
        $response->assertDontSee(route('hafalan-targets.index'));
        $response->assertDontSee(route('reports.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee(route('digital-reports.index'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function student_menu_visibility(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('hafalan-records.index'));
        $response->assertDontSee(route('murajaah-records.index'));
        $response->assertDontSee(route('hafalan-targets.index'));
        $response->assertDontSee(route('reports.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee(route('digital-reports.index'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function supervisor_menu_visibility(): void
    {
        $roleSupervisor = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor']);
        $supervisorUser = User::factory()->create([
            'role_id' => $roleSupervisor->id,
            'name' => 'Supervisor Test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($supervisorUser)->get(route('supervisor.dashboard'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee('href="' . route('reports.index') . '"');
        $response->assertDontSee(route('digital-reports.index'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('hafalan-records.index'));
        $response->assertSee(route('spreadsheet-input.index'));
        $response->assertSee(route('hafalan-targets.index'));
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('adab.index'));
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function headmaster_menu_visibility(): void
    {
        $roleHeadmaster = Role::firstOrCreate(['name' => 'headmaster'], ['display_name' => 'Kepala Sekolah']);
        $headmasterUser = User::factory()->create([
            'role_id' => $roleHeadmaster->id,
            'name' => 'Kepsek Test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($headmasterUser)->get(route('reports.periodic'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('hafalan-records.index'));
        $response->assertDontSee(route('murajaah-records.index'));
        $response->assertDontSee(route('hafalan-targets.index'));
        $response->assertDontSee('href="' . route('adab.index') . '"');
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Restricted for Headmaster
        $response->assertDontSee('Mushaf Al-Qur\'an');
        $response->assertDontSee('<span>Progress</span>');
        $response->assertDontSee('Rapor Digital');
        $response->assertDontSee('Kinerja Guru');
        $response->assertDontSee('href="' . route('student-points.index') . '"');

        // Should see
        $response->assertSee(route('reports.periodic'));
        $response->assertSee(route('adab.chart'));
        $response->assertSee(route('student-points.chart'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function coordinator_tahfizh_menu_visibility(): void
    {
        $roleCoord = Role::firstOrCreate(['name' => 'coordinator_tahfizh'], ['display_name' => 'Koordinator Tahfizh']);
        $coordUser = User::factory()->create([
            'role_id' => $roleCoord->id,
            'name' => 'Koordinator Test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($coordUser)->get(route('progress.index'));

        $response->assertStatus(200);
        // Should NOT see
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee(route('adab.index'));
        $response->assertDontSee(route('student-points.index'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('hafalan-records.index'));
        $response->assertSee(route('spreadsheet-input.index'));
        $response->assertSee(route('hafalan-targets.index'));
        $response->assertSee(route('quran.mushaf'));
        $response->assertSee(route('progress.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('digital-reports.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function tanse_menu_visibility(): void
    {
        // Setup tanse user
        $roleTanse = Role::firstOrCreate(['name' => 'tanse'], ['display_name' => 'Tanse']);
        $tanseUser = User::factory()->create([
            'role_id' => $roleTanse->id,
            'name' => 'Tanse Test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($tanseUser)->get(route('student-points.index'));
        $response->assertStatus(200);

        // Should NOT see menus they cannot access
        $response->assertDontSee(route('programs.index'));
        $response->assertDontSee(route('class-rooms.index'));
        $response->assertDontSee(route('teachers.index'));
        $response->assertDontSee(route('parents.index'));
        $response->assertDontSee(route('students.index'));

        $response->assertDontSee(route('hafalan-records.index'));
        $response->assertDontSee(route('murajaah-records.index'));
        $response->assertDontSee(route('hafalan-targets.index'));
        $response->assertDontSee(route('progress.index'));
        $response->assertDontSee(route('reports.index'));
        $response->assertDontSee(route('reports.teachers'));
        $response->assertDontSee(route('digital-reports.index'));
        $response->assertDontSee(route('quran.mushaf'));
        $response->assertDontSee(route('adab.index'));
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('settings.index'));

        // Should see
        $response->assertSee(route('student-points.index'));
        $response->assertSee(route('system-notifications.index'));
    }

    #[Test]
    public function super_admin_can_access_class_print_route(): void
    {
        $classRoom = ClassRoom::first();
        $response = $this->actingAs($this->superAdmin)->get(route('digital-reports.class-print', $classRoom));

        $response->assertStatus(200);
        $response->assertViewIs('reports.digital-report-class-print');
        $response->assertSee('Cetak Masal Rapor Kelas');
        $response->assertSee($this->student->name);
    }
}
