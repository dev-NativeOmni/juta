<?php

namespace Tests\Feature\Policies;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Policies\HafalanTargetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HafalanTargetPolicyTest extends TestCase
{
    use RefreshDatabase;

    private HafalanTargetPolicy $policy;

    // Roles
    private Role $superAdminRole;

    private Role $adminRole;

    private Role $teacherRole;

    private Role $parentRole;

    private Role $studentRole;

    private Role $headmasterRole;

    private Role $coordinatorRole;

    private Role $tanseRole;

    private Role $supervisorRole;

    // Users
    private User $superAdmin;

    private User $admin;

    private User $headmaster;

    private User $coordinator;

    private User $supervisor;

    private User $tanse;

    // specific users
    private User $teacherUserLinked;

    private User $teacherUserUnlinked;

    private User $parentUserLinked;

    private User $parentUserUnlinked;

    private User $studentUserLinked;

    private User $studentUserUnlinked;

    private Student $student;

    private HafalanTarget $hafalanTarget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new HafalanTargetPolicy;

        // Seed roles
        $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $this->teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $this->parentRole = Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orang Tua']);
        $this->studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);
        $this->headmasterRole = Role::firstOrCreate(['name' => 'headmaster'], ['display_name' => 'Kepala Sekolah']);
        $this->coordinatorRole = Role::firstOrCreate(['name' => 'coordinator_tahfizh'], ['display_name' => 'Koordinator Tahfizh']);
        $this->tanseRole = Role::firstOrCreate(['name' => 'tanse'], ['display_name' => 'Tanse']);
        $this->supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor']);

        // Create generic users
        $this->superAdmin = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $this->headmaster = User::factory()->create(['role_id' => $this->headmasterRole->id]);
        $this->coordinator = User::factory()->create(['role_id' => $this->coordinatorRole->id]);
        $this->supervisor = User::factory()->create(['role_id' => $this->supervisorRole->id]);
        $this->tanse = User::factory()->create(['role_id' => $this->tanseRole->id]);

        // Create linked teacher
        $this->teacherUserLinked = User::factory()->create(['role_id' => $this->teacherRole->id]);
        $teacherLinked = TeacherProfile::create([
            'user_id' => $this->teacherUserLinked->id,
            'employee_number' => 'T-001',
            'phone' => '081111111111',
        ]);

        // Create unlinked teacher
        $this->teacherUserUnlinked = User::factory()->create(['role_id' => $this->teacherRole->id]);
        TeacherProfile::create([
            'user_id' => $this->teacherUserUnlinked->id,
            'employee_number' => 'T-002',
            'phone' => '081111111112',
        ]);

        // Programs & ClassRooms for student
        $program = Program::create(['name' => 'Tahfizh Reguler', 'status' => 'active']);
        $classRoom = ClassRoom::create(['name' => 'Kelas X-A', 'level' => '10', 'program_id' => $program->id]);

        // Linked Student
        $this->studentUserLinked = User::factory()->create(['role_id' => $this->studentRole->id]);
        $this->student = Student::create([
            'user_id' => $this->studentUserLinked->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacherLinked->id,
            'name' => 'Student Linked',
            'student_number' => 'S-001',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        // Unlinked Student
        $this->studentUserUnlinked = User::factory()->create(['role_id' => $this->studentRole->id]);
        Student::create([
            'user_id' => $this->studentUserUnlinked->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacherLinked->id,
            'name' => 'Student Unlinked',
            'student_number' => 'S-002',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        // Linked Parent
        $this->parentUserLinked = User::factory()->create(['role_id' => $this->parentRole->id]);
        $parentLinked = ParentProfile::create([
            'user_id' => $this->parentUserLinked->id,
            'phone' => '082222222222',
            'address' => 'Test Address',
        ]);
        $this->student->parents()->attach($parentLinked->id, ['relation' => 'ayah']);

        // Unlinked Parent
        $this->parentUserUnlinked = User::factory()->create(['role_id' => $this->parentRole->id]);
        ParentProfile::create([
            'user_id' => $this->parentUserUnlinked->id,
            'phone' => '082222222223',
            'address' => 'Test Address 2',
        ]);

        // Hafalan Target
        $surah = Surah::create(['number' => 1, 'name_latin' => 'Al-Fatihah', 'total_ayah' => 7]);
        $this->hafalanTarget = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $teacherLinked->id,
            'surah_id' => $surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => today()->addDays(7),
            'status' => 'active',
        ]);
    }

    #[Test]
    public function view_any_allows_specific_roles(): void
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->teacherUserLinked));
        $this->assertTrue($this->policy->viewAny($this->parentUserLinked));
        $this->assertTrue($this->policy->viewAny($this->studentUserLinked));
        $this->assertTrue($this->policy->viewAny($this->headmaster));
        $this->assertTrue($this->policy->viewAny($this->coordinator));

        // These shouldn't have access
        $this->assertFalse($this->policy->viewAny($this->tanse));
        $this->assertFalse($this->policy->viewAny($this->supervisor));
    }

    #[Test]
    public function view_logic_from_user_access_service(): void
    {
        // Admin, Headmaster, Supervisor, Coordinator can view any student
        $this->assertTrue($this->policy->view($this->superAdmin, $this->hafalanTarget));
        $this->assertTrue($this->policy->view($this->admin, $this->hafalanTarget));
        $this->assertTrue($this->policy->view($this->headmaster, $this->hafalanTarget));
        $this->assertTrue($this->policy->view($this->supervisor, $this->hafalanTarget));
        $this->assertTrue($this->policy->view($this->coordinator, $this->hafalanTarget));

        // Tanse cannot view
        $this->assertFalse($this->policy->view($this->tanse, $this->hafalanTarget));

        // Teacher linked vs unlinked
        $this->assertTrue($this->policy->view($this->teacherUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->view($this->teacherUserUnlinked, $this->hafalanTarget));

        // Parent linked vs unlinked
        $this->assertTrue($this->policy->view($this->parentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->view($this->parentUserUnlinked, $this->hafalanTarget));

        // Student viewing own vs others
        $this->assertTrue($this->policy->view($this->studentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->view($this->studentUserUnlinked, $this->hafalanTarget));
    }

    #[Test]
    public function create_allows_admin_and_teacher(): void
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertTrue($this->policy->create($this->teacherUserLinked));
        $this->assertTrue($this->policy->create($this->teacherUserUnlinked)); // Allowed to create in general, but actual student select is limited by service usually

        $this->assertFalse($this->policy->create($this->headmaster));
        $this->assertFalse($this->policy->create($this->coordinator));
        $this->assertFalse($this->policy->create($this->parentUserLinked));
        $this->assertFalse($this->policy->create($this->studentUserLinked));
        $this->assertFalse($this->policy->create($this->tanse));
        $this->assertFalse($this->policy->create($this->supervisor));
    }

    #[Test]
    public function update_allows_admin_and_linked_teacher(): void
    {
        // Admins can update
        $this->assertTrue($this->policy->update($this->superAdmin, $this->hafalanTarget));
        $this->assertTrue($this->policy->update($this->admin, $this->hafalanTarget));

        // Linked teacher can update
        $this->assertTrue($this->policy->update($this->teacherUserLinked, $this->hafalanTarget));

        // Unlinked teacher cannot update
        $this->assertFalse($this->policy->update($this->teacherUserUnlinked, $this->hafalanTarget));

        // Others cannot update
        $this->assertFalse($this->policy->update($this->headmaster, $this->hafalanTarget));
        $this->assertFalse($this->policy->update($this->coordinator, $this->hafalanTarget));
        $this->assertFalse($this->policy->update($this->parentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->update($this->studentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->update($this->tanse, $this->hafalanTarget));
        $this->assertFalse($this->policy->update($this->supervisor, $this->hafalanTarget));
    }

    #[Test]
    public function delete_allows_admin_and_linked_teacher(): void
    {
        // Admins can delete
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->hafalanTarget));
        $this->assertTrue($this->policy->delete($this->admin, $this->hafalanTarget));

        // Linked teacher can delete
        $this->assertTrue($this->policy->delete($this->teacherUserLinked, $this->hafalanTarget));

        // Unlinked teacher cannot delete
        $this->assertFalse($this->policy->delete($this->teacherUserUnlinked, $this->hafalanTarget));

        // Others cannot delete
        $this->assertFalse($this->policy->delete($this->headmaster, $this->hafalanTarget));
        $this->assertFalse($this->policy->delete($this->coordinator, $this->hafalanTarget));
        $this->assertFalse($this->policy->delete($this->parentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->delete($this->studentUserLinked, $this->hafalanTarget));
        $this->assertFalse($this->policy->delete($this->tanse, $this->hafalanTarget));
        $this->assertFalse($this->policy->delete($this->supervisor, $this->hafalanTarget));
    }
}
