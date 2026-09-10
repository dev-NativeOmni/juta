<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CoreDataSeeder::class,
        ]);
    }

    public function test_super_admin_can_assign_multiple_roles_to_user(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $teacher = User::where('username', 'guru')->first();

        $teacherRole = Role::where('name', 'teacher')->first();
        $coordinatorRole = Role::where('name', 'coordinator_tahfizh')->first();
        $adminRole = Role::where('name', 'admin')->first();

        $response = $this->actingAs($superAdmin)->patch(route('users.update', $teacher), [
            'name' => 'Guru Multitasking',
            'username' => 'guru',
            'role_id' => $teacherRole->id,
            'additional_role_ids' => [$coordinatorRole->id, $adminRole->id],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));

        $teacher->refresh();
        $assignedIds = $teacher->assignedRoles()->pluck('id')->all();

        $this->assertContains($teacherRole->id, $assignedIds);
        $this->assertContains($coordinatorRole->id, $assignedIds);
        $this->assertContains($adminRole->id, $assignedIds);
    }

    public function test_user_can_switch_to_assigned_role(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $coordinatorRole = Role::where('name', 'coordinator_tahfizh')->first();

        // Assign both roles
        $teacher->roles()->sync([$teacherRole->id, $coordinatorRole->id]);

        $this->actingAs($teacher);

        // Initially active role is teacher
        $this->assertTrue($teacher->hasRole('teacher'));
        $this->assertFalse($teacher->hasRole('coordinator_tahfizh'));

        // Switch to coordinator_tahfizh
        $response = $this->post(route('role.switch'), [
            'role_id' => $coordinatorRole->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('active_role_id', $coordinatorRole->id);

        // Now hasRole reflects the switched role
        $this->assertTrue($teacher->hasRole('coordinator_tahfizh'));
        $this->assertFalse($teacher->hasRole('teacher'));

        // Visiting /dashboard redirects to coordinator tahfizh dashboard
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertRedirect(route('coordinator-tahfizh.dashboard'));
    }

    public function test_user_cannot_switch_to_unassigned_role(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $response = $this->actingAs($teacher)->post(route('role.switch'), [
            'role_id' => $superAdminRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('active_role_id'));
    }

    public function test_profile_page_displays_role_switcher_for_multi_role_user(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $coordinatorRole = Role::where('name', 'coordinator_tahfizh')->first();

        // Single role -> no switcher card
        $teacher->roles()->sync([$teacherRole->id]);
        $response1 = $this->actingAs($teacher)->get(route('profile.edit'));
        $response1->assertDontSee('Ganti Peran Aktif (Role Switcher)');

        // Multiple roles -> switcher card displayed
        $teacher->roles()->sync([$teacherRole->id, $coordinatorRole->id]);
        $teacher->unsetRelation('roles');
        $response2 = $this->actingAs($teacher)->get(route('profile.edit'));
        $response2->assertSee('Ganti Peran Aktif (Role Switcher)');
        $response2->assertSee('Koordinator Tahfizh');
    }

    public function test_teacher_with_pendamping_adab_side_role_can_be_assigned_to_class_and_access_dashboard(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $teacher = User::where('username', 'guru')->first();
        $pendampingRole = Role::where('name', 'pendamping_adab')->first();
        $teacherRole = Role::where('name', 'teacher')->first();

        // Assign pendamping_adab as side role
        $teacher->roles()->sync([$teacherRole->id, $pendampingRole->id]);

        // Superadmin creates or edits a class and sees teacher in pendampingList
        $response = $this->actingAs($superAdmin)->get(route('class-rooms.create'));
        $response->assertStatus(200);
        $response->assertSee($teacher->name);

        // Assign teacher as pendamping_adab for class
        $classRoom = \App\Models\ClassRoom::first();
        $classRoom->update(['pendamping_adab_id' => $teacher->id]);

        // Teacher switches to pendamping_adab role
        $this->actingAs($teacher)->post(route('role.switch'), [
            'role_id' => $pendampingRole->id,
        ])->assertRedirect(route('dashboard'));

        // Visiting dashboard directs to pendamping-adab dashboard
        $dashResponse = $this->actingAs($teacher)->get(route('pendamping-adab.dashboard'));
        $dashResponse->assertStatus(200);
    }
}
