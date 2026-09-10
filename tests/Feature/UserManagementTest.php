<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_guest_and_non_super_admin_cannot_access_user_management(): void
    {
        // Guest
        $this->get(route('users.index'))->assertRedirect(route('login'));

        // Admin (non-super-admin)
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'testadmin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_users_list_and_plain_passwords(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'name' => 'AAA Super Admin',
            'username' => 'testsuperadmin',
            'status' => 'active',
            'plain_password' => 'supersecret123',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Akun User');
        $response->assertSee('supersecret123'); // Assert plain-text password is visible
    }

    public function test_super_admin_can_update_user_credentials(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'username' => 'oldusername',
            'status' => 'active',
            'plain_password' => 'oldpassword',
        ]);

        // Update to new credentials
        $response = $this->actingAs($superAdmin)
            ->patch(route('users.update', $studentUser), [
                'name' => 'New Student Name',
                'username' => 'newusername',
                'role_id' => $studentRole->id,
                'password' => 'newawesomepassword123',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('users.index'));

        // Assert DB updated
        $studentUser->refresh();
        $this->assertEquals('newusername', $studentUser->username);
        $this->assertEquals('New Student Name', $studentUser->name);
        $this->assertEquals('newawesomepassword123', $studentUser->plain_password);
        $this->assertTrue(Hash::check('newawesomepassword123', $studentUser->password));
    }

    public function test_super_admin_can_access_user_edit_page_with_class_assignments(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $teacher = User::where('username', 'guru')->first();

        $response = $this->actingAs($superAdmin)->get(route('users.edit', $teacher));
        $response->assertStatus(200);
        $response->assertSee('Penugasan Kelas Pendamping Adab');
    }

    public function test_super_admin_can_update_user_with_pendamping_classes(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $teacher = User::where('username', 'guru')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $pendampingRole = Role::where('name', 'pendamping_adab')->first();

        $program = \App\Models\Program::create(['name' => 'Program Test', 'status' => 'active']);
        $classRoom = \App\Models\ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Test Pendamping',
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('users.update', $teacher), [
            'name' => 'Guru Pendamping',
            'username' => 'guru',
            'role_id' => $teacherRole->id,
            'additional_role_ids' => [$pendampingRole->id],
            'pendamping_class_ids' => [$classRoom->id],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertTrue($teacher->isAssignedPendampingForClass($classRoom->id));
    }

    public function test_super_admin_can_create_new_user(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin2',
            'status' => 'active',
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();

        // Access create form
        $this->actingAs($superAdmin)
            ->get(route('users.create'))
            ->assertStatus(200);

        // Store new user
        $response = $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Guru Baru Tahfidz',
                'username' => 'gurubaru',
                'role_id' => $teacherRole->id,
                'password' => 'secretpwd123',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru Tahfidz',
            'username' => 'gurubaru',
            'role_id' => $teacherRole->id,
            'plain_password' => 'secretpwd123',
            'status' => 'active',
        ]);
    }

    public function test_non_super_admin_cannot_create_new_user(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'testadmin',
            'status' => 'active',
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();

        // Try to access create form
        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertStatus(403);

        // Try to store new user
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Guru Baru Tahfidz',
                'username' => 'gurubaru',
                'role_id' => $teacherRole->id,
                'password' => 'secretpwd123',
                'status' => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_super_admin_can_filter_users_by_status(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $activeUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'username' => 'activeteacher',
            'status' => 'active',
            'name' => 'AAA Active Teacher Name',
        ]);

        $inactiveUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'username' => 'inactiveteacher',
            'status' => 'inactive',
            'name' => 'AAA Inactive Teacher Name',
        ]);

        // Filter active
        $responseActive = $this->actingAs($superAdmin)->get(route('users.index', ['status' => 'active']));
        $responseActive->assertStatus(200);
        $responseActive->assertSee('AAA Active Teacher Name');
        $responseActive->assertDontSee('AAA Inactive Teacher Name');

        // Filter inactive
        $responseInactive = $this->actingAs($superAdmin)->get(route('users.index', ['status' => 'inactive']));
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('AAA Inactive Teacher Name');
        $responseInactive->assertDontSee('AAA Active Teacher Name');
    }

    public function test_super_admin_can_delete_other_user(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'username' => 'teacherdelete',
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('users.destroy', $teacher));
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', [
            'id' => $teacher->id,
        ]);
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('users.destroy', $superAdmin));
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'deleted_at' => null,
        ]);
    }

    public function test_non_super_admin_cannot_delete_user(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'testadmin',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'username' => 'teacherdelete',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $teacher))
            ->assertStatus(403);
    }
}
