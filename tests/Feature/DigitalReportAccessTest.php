<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Rapor Digital (lihat saja) diperluas ke: super_admin, admin, teacher,
 * coordinator_tahfizh, pendamping_adab (koor adab), tanse, headmaster,
 * wali_kelas -- sebelumnya cuma 5 role pertama. Pengaturan Rapor (settings,
 * ubah tahun ajaran/template global) dibatasi lebih ketat lagi: khusus
 * super_admin & admin, karena action itu tidak punya pengecekan role
 * sendiri di controller (murni mengandalkan middleware route).
 */
class DigitalReportAccessTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function makeUser(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);

        return User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
    }

    #[Test]
    public function newly_granted_roles_can_view_the_digital_report_index(): void
    {
        foreach (['coordinator_tahfizh', 'pendamping_adab', 'tanse', 'headmaster'] as $roleName) {
            $user = $this->makeUser($roleName, $roleName);
            $this->actingAs($user)->get(route('digital-reports.index'))->assertOk();
        }

        $waliKelasRole = Role::firstOrCreate(['name' => 'wali_kelas'], ['display_name' => 'Wali Kelas']);
        $waliKelasUser = User::factory()->create(['role_id' => $waliKelasRole->id, 'status' => 'active']);
        $this->classRoom()->update(['wali_kelas_user_id' => $waliKelasUser->id]);
        $this->actingAs($waliKelasUser)->get(route('digital-reports.index'))->assertOk();
    }

    #[Test]
    public function wali_kelas_can_only_see_their_own_class_students_report(): void
    {
        $waliKelasRole = Role::firstOrCreate(['name' => 'wali_kelas'], ['display_name' => 'Wali Kelas']);
        $waliKelasUser = User::factory()->create(['role_id' => $waliKelasRole->id, 'status' => 'active']);
        $ownClass = $this->classRoom();
        $ownClass->update(['wali_kelas_user_id' => $waliKelasUser->id]);
        $this->student->update(['class_room_id' => $ownClass->id]);

        $otherClass = ClassRoom::create([
            'program_id' => $ownClass->program_id,
            'name' => 'Kelas Lain Wali',
            'level' => 'XII',
        ]);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-DR-001',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $this->actingAs($waliKelasUser)->get(route('digital-reports.show', $this->student))->assertOk();
        $this->actingAs($waliKelasUser)->get(route('digital-reports.show', $otherStudent))->assertForbidden();
    }

    #[Test]
    public function pendamping_adab_can_only_see_students_in_their_assigned_class(): void
    {
        $pendampingRole = Role::firstOrCreate(['name' => 'pendamping_adab'], ['display_name' => 'Pendamping Adab']);
        $pendampingUser = User::factory()->create(['role_id' => $pendampingRole->id, 'status' => 'active']);
        $ownClass = $this->classRoom();
        $ownClass->update(['pendamping_adab_id' => $pendampingUser->id]);
        $this->student->update(['class_room_id' => $ownClass->id]);

        $otherClass = ClassRoom::create([
            'program_id' => $ownClass->program_id,
            'name' => 'Kelas Lain Adab',
            'level' => 'XII',
        ]);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'name' => 'Murid Kelas Lain Adab',
            'student_number' => 'TEST-DR-002',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $this->actingAs($pendampingUser)->get(route('digital-reports.show', $this->student))->assertOk();
        $this->actingAs($pendampingUser)->get(route('digital-reports.show', $otherStudent))->assertForbidden();
    }

    #[Test]
    public function only_super_admin_and_admin_can_access_report_settings(): void
    {
        $this->actingAs($this->superAdmin)->get(route('digital-reports.settings'))->assertOk();
        $this->actingAs($this->admin)->get(route('digital-reports.settings'))->assertOk();

        foreach (['teacher', 'coordinator_tahfizh', 'tanse', 'pendamping_adab', 'headmaster', 'wali_kelas'] as $roleName) {
            $user = $this->makeUser($roleName, $roleName);
            $this->actingAs($user)->get(route('digital-reports.settings'))->assertForbidden();
        }
    }

    private function classRoom(): ClassRoom
    {
        return $this->student->classRoom;
    }
}
