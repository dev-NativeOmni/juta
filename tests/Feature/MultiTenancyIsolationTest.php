<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\InstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultiTenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $inst1;

    protected Institution $inst2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inst1 = Institution::create([
            'code' => 'SEKOLAH01',
            'name' => 'Sekolah Islam Terpadu Satu',
            'slug' => 'sekolah-islam-terpadu-satu',
            'is_active' => true,
        ]);

        $this->inst2 = Institution::create([
            'code' => 'SEKOLAH02',
            'name' => 'Pesantren Tahfizh Dua',
            'slug' => 'pesantren-tahfizh-dua',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function models_automatically_assign_active_institution_id_on_create(): void
    {
        app(InstitutionContext::class)->set($this->inst1);

        $program = Program::create(['name' => 'Program Tahfizh Reguler', 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas VII A', 'level' => 'VII']);
        $student = Student::create([
            'name' => 'Ahmad Santri',
            'class_room_id' => $class->id,
            'student_number' => 'NIS001',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $this->assertEquals($this->inst1->id, $program->institution_id);
        $this->assertEquals($this->inst1->id, $class->institution_id);
        $this->assertEquals($this->inst1->id, $student->institution_id);
    }

    #[Test]
    public function query_scope_isolates_data_between_different_institutions(): void
    {
        // Create Data in Institution 1
        app(InstitutionContext::class)->set($this->inst1);
        $prog1 = Program::create(['name' => 'Prog Inst 1', 'status' => 'active']);
        $class1 = ClassRoom::create(['program_id' => $prog1->id, 'name' => 'Kelas 1', 'level' => 'X']);
        $std1 = Student::create(['name' => 'Santri Inst 1', 'class_room_id' => $class1->id, 'student_number' => 'NIS-1', 'status' => 'active']);

        // Create Data in Institution 2
        app(InstitutionContext::class)->set($this->inst2);
        $prog2 = Program::create(['name' => 'Prog Inst 2', 'status' => 'active']);
        $class2 = ClassRoom::create(['program_id' => $prog2->id, 'name' => 'Kelas 2', 'level' => 'X']);
        $std2 = Student::create(['name' => 'Santri Inst 2', 'class_room_id' => $class2->id, 'student_number' => 'NIS-2', 'status' => 'active']);

        // When context is Institution 1
        app(InstitutionContext::class)->set($this->inst1);
        $this->assertCount(1, Student::all());
        $this->assertEquals('Santri Inst 1', Student::first()->name);
        $this->assertNull(Student::find($std2->id));

        // When context is Institution 2
        app(InstitutionContext::class)->set($this->inst2);
        $this->assertCount(1, Student::all());
        $this->assertEquals('Santri Inst 2', Student::first()->name);
        $this->assertNull(Student::find($std1->id));

        // Without scope (e.g. Platform Administrator)
        $this->assertCount(2, Student::withoutInstitution()->get());
    }

    #[Test]
    public function user_cannot_login_to_different_institution_portal(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        // User belongs to Institution 1
        $user = User::factory()->create([
            'institution_id' => $this->inst1->id,
            'role_id' => $teacherRole->id,
            'username' => 'guru.inst1',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Trying to login to Institution 2 portal
        session([InstitutionContext::SESSION_KEY => $this->inst2->id]);
        app(InstitutionContext::class)->clear();
        app(InstitutionContext::class)->set($this->inst2);

        $response = $this->post(route('login'), [
            'username' => 'guru.inst1',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    #[Test]
    public function user_can_login_to_matching_institution_portal(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $user = User::factory()->create([
            'institution_id' => $this->inst1->id,
            'role_id' => $teacherRole->id,
            'username' => 'guru.inst1',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Login to Institution 1 portal
        session([InstitutionContext::SESSION_KEY => $this->inst1->id]);
        app(InstitutionContext::class)->clear();
        app(InstitutionContext::class)->set($this->inst1);

        $response = $this->post(route('login'), [
            'username' => 'guru.inst1',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    #[Test]
    public function super_admin_can_login_regardless_of_active_portal(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);

        $superAdmin = User::factory()->create([
            'institution_id' => null, // platform level
            'role_id' => $superAdminRole->id,
            'username' => 'superadmin.global',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Active portal is Institution 2
        session([InstitutionContext::SESSION_KEY => $this->inst2->id]);
        app(InstitutionContext::class)->clear();
        app(InstitutionContext::class)->set($this->inst2);

        $response = $this->post(route('login'), [
            'username' => 'superadmin.global',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($superAdmin);
    }
}
