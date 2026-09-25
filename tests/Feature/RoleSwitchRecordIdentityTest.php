<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Regresi untuk bug: setoran yang diinput guru saat sedang aktif di side-role
 * lain (mis. admin) tercatat atas nama guru pembimbing MURID, bukan guru yang
 * benar-benar login & input — sehingga hilang dari daftar "setoran saya"
 * ketika guru kembali ke role aslinya.
 */
class RoleSwitchRecordIdentityTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function record_stored_while_switched_to_side_role_is_still_attributed_to_the_acting_teacher(): void
    {
        // $this->student sudah dibimbing oleh $this->teacherProfile (guru lain).
        // Buat guru KEDUA yang akan bertindak (login) dan diberi side-role admin.
        $actingTeacherUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'name' => 'Guru Kedua (Aktor)',
            'status' => 'active',
        ]);
        $actingTeacherProfile = TeacherProfile::create([
            'user_id' => $actingTeacherUser->id,
            'employee_number' => 'TEST-GURU-002',
            'phone' => '081100002222',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $actingTeacherUser->roles()->sync([
            Role::where('name', 'teacher')->first()->id,
            $adminRole->id,
        ]);

        // Guru kedua beralih ke side-role admin.
        $this->actingAs($actingTeacherUser)
            ->post(route('role.switch'), ['role_id' => $adminRole->id])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($actingTeacherUser->hasRole('admin'));
        $this->assertFalse($actingTeacherUser->hasRole('teacher'));

        // Sambil aktif sebagai admin, ia input setoran untuk murid yang
        // sebenarnya dibimbing guru LAIN ($this->teacherProfile).
        $response = $this->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));

        // Harus tercatat atas nama guru yang benar-benar login & input,
        // BUKAN guru pembimbing murid ($this->teacherProfile).
        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'teacher_id' => $actingTeacherProfile->id,
        ]);
        $this->assertDatabaseMissing('hafalan_records', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
        ]);

        // Sehingga saat guru kedua kembali ke role aslinya, setoran ini
        // harus tetap tampil di daftar "setoran saya".
        session()->forget('active_role_id');
        $indexResponse = $this->actingAs($actingTeacherUser)->get(route('hafalan-records.index'));
        $indexResponse->assertStatus(200);

        $record = HafalanRecord::where('student_id', $this->student->id)->firstOrFail();
        $indexResponse->assertSee($record->surahs->first()->surah->name_latin);
    }

    #[Test]
    public function real_musyrif_still_sees_setoran_entered_by_another_teacher_acting_as_admin(): void
    {
        // $this->student is guided by $this->teacherProfile ($this->teacherUser).
        $actingTeacherUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'name' => 'Guru Kedua (Aktor)',
            'status' => 'active',
        ]);
        TeacherProfile::create([
            'user_id' => $actingTeacherUser->id,
            'employee_number' => 'TEST-GURU-003',
            'phone' => '081100003333',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $actingTeacherUser->roles()->sync([
            Role::where('name', 'teacher')->first()->id,
            $adminRole->id,
        ]);

        $this->actingAs($actingTeacherUser)
            ->post(route('role.switch'), ['role_id' => $adminRole->id])
            ->assertRedirect(route('dashboard'));

        // Sambil aktif sebagai admin, guru kedua input setoran untuk murid
        // yang musyrif aslinya adalah $this->teacherProfile.
        $this->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ])->assertRedirect(route('hafalan-records.index'));

        $record = HafalanRecord::where('student_id', $this->student->id)->firstOrFail();
        $this->assertNotEquals($this->teacherProfile->id, $record->teacher_id);

        // Musyrif ASLI murid ini ($this->teacherUser) harus tetap melihat setoran
        // tersebut di daftar "store hafalan"-nya, walau bukan dia yang menginput.
        // Dicek lewat data view-nya langsung (bukan assertSee) karena nama surah
        // juga selalu muncul di dropdown filter terlepas dari isi daftarnya.
        $indexResponse = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertViewHas('hafalanRecords', function ($records) use ($record) {
            return $records->contains('id', $record->id);
        });
    }
}
