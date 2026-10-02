<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\InstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuperAdminInstitutionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function guest_and_non_super_admin_cannot_access_institutions_management(): void
    {
        $this->get(route('institutions.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->teacher)
            ->get(route('institutions.index'))
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_can_view_institutions_index_and_stats(): void
    {
        $inst = Institution::create([
            'name' => 'Pesantren Darul Ulum',
            'code' => 'DULUM01',
            'slug' => 'darul-ulum',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('institutions.index'));

        $response->assertStatus(200);
        $response->assertSee('Pesantren Darul Ulum');
        $response->assertSee('DULUM01');
    }

    #[Test]
    public function super_admin_can_create_new_institution_with_valid_data(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('school_logo.png', 100, 100);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('institutions.store'), [
                'name' => 'SMAIT Al-Fath Jakarta',
                'code' => 'alfath01', // testing auto-uppercase
                'slug' => 'smait-al-fath',
                'email' => 'info@alfath.sch.id',
                'phone' => '08123456789',
                'address' => 'Jl. TB Simatupang No. 99, Jakarta Selatan',
                'is_active' => '1',
                'logo' => $logo,
            ]);

        $response->assertRedirect(route('institutions.index'));

        $this->assertDatabaseHas('institutions', [
            'name' => 'SMAIT Al-Fath Jakarta',
            'code' => 'ALFATH01',
            'slug' => 'smait-al-fath',
            'email' => 'info@alfath.sch.id',
            'is_active' => true,
        ]);

        $created = Institution::where('code', 'ALFATH01')->first();
        $this->assertNotNull($created->logo_path);
        Storage::disk('public')->assertExists($created->logo_path);
    }

    #[Test]
    public function super_admin_cannot_create_institution_with_duplicate_code(): void
    {
        Institution::create([
            'name' => 'Sekolah Pertama',
            'code' => 'SEKOLAH01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('institutions.store'), [
                'name' => 'Sekolah Kedua',
                'code' => 'SEKOLAH01',
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function super_admin_can_update_institution_details(): void
    {
        $inst = Institution::create([
            'name' => 'Nama Awal Lembaga',
            'code' => 'LEMBAGA01',
            'slug' => 'nama-awal-lembaga',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('institutions.update', $inst), [
                'name' => 'Nama Lembaga Baru',
                'code' => 'LEMBAGA01',
                'slug' => 'nama-lembaga-baru',
                'email' => 'kontak@baru.sch.id',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('institutions.index'));

        $this->assertDatabaseHas('institutions', [
            'id' => $inst->id,
            'name' => 'Nama Lembaga Baru',
            'slug' => 'nama-lembaga-baru',
            'email' => 'kontak@baru.sch.id',
        ]);
    }

    #[Test]
    public function super_admin_can_switch_active_institution_context(): void
    {
        $inst = Institution::create([
            'name' => 'Pesantren Tahfizh Putri',
            'code' => 'PUTRI01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('institutions.switch'), [
                'institution_id' => $inst->id,
            ]);

        $response->assertSessionHas(InstitutionContext::SESSION_KEY, $inst->id);
    }

    #[Test]
    public function super_admin_can_reset_to_global_context(): void
    {
        $inst = Institution::create([
            'name' => 'Pesantren Tahfizh Putra',
            'code' => 'PUTRA01',
            'is_active' => true,
        ]);

        session([InstitutionContext::SESSION_KEY => $inst->id]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('institutions.switch'), [
                'institution_id' => 'global',
            ]);

        $response->assertSessionMissing(InstitutionContext::SESSION_KEY);
    }

    #[Test]
    public function institution_with_students_is_deactivated_instead_of_hard_deleted(): void
    {
        $inst = Institution::create([
            'name' => 'Pesantren Ramai Santri',
            'code' => 'RAMAI01',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $inst->id,
            'name' => 'Santri Aktif',
            'student_number' => 'NIS-RAMAI',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('institutions.destroy', $inst));

        $response->assertRedirect(route('institutions.index'));

        // Soft deactivation
        $inst->refresh();
        $this->assertFalse($inst->is_active);
    }
}
