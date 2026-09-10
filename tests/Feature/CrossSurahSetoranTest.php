<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossSurahSetoranTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;

    private TeacherProfile $teacher;

    private Student $student;

    private Surah $surah1;

    private Surah $surah2;

    private Surah $surah3;

    protected function setUp(): void
    {
        parent::setUp();

        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);

        $this->teacherUser = User::factory()->create(['role_id' => $teacherRole->id, 'status' => 'active']);
        $this->teacher = TeacherProfile::create([
            'user_id' => $this->teacherUser->id,
            'employee_number' => 'T-001',
            'phone' => '081111111111',
        ]);

        $program = Program::create(['name' => 'Reguler', 'status' => 'active']);
        $classRoom = ClassRoom::create(['name' => 'Kelas XI-A', 'level' => '11', 'program_id' => $program->id]);

        $userStudent = User::factory()->create(['role_id' => $studentRole->id]);
        $this->student = Student::create([
            'user_id' => $userStudent->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Reguler',
            'student_number' => 'S-001',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        $this->surah1 = Surah::create(['number' => 1, 'name_arabic' => 'الفاتحة', 'name_latin' => 'Al-Fatihah', 'total_ayah' => 7, 'revelation_type' => 'meccan']);
        $this->surah2 = Surah::create(['number' => 2, 'name_arabic' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'revelation_type' => 'medinan']);
        $this->surah3 = Surah::create(['number' => 3, 'name_arabic' => 'آل عمران', 'name_latin' => "Ali 'Imran", 'total_ayah' => 200, 'revelation_type' => 'medinan']);
    }

    public function test_single_surah_hafalan_creates_one_record()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah1->id,
            'surah_end_id' => $this->surah1->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
            'submitted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertDatabaseCount('hafalan_records', 1);
        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah1->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
        ]);
    }

    public function test_cross_surah_hafalan_splits_into_multiple_records()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah1->id,
            'surah_end_id' => $this->surah2->id,
            'ayah_start' => 5,
            'ayah_end' => 10,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 95,
            'submitted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));

        // Should create 2 records:
        // Record 1: Surah 1, Ayah 5 to 7 (end of Al-Fatihah)
        // Record 2: Surah 2, Ayah 1 to 10
        $this->assertDatabaseCount('hafalan_records', 2);

        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah1->id,
            'ayah_start' => 5,
            'ayah_end' => 7,
            'score' => 95.00,
        ]);

        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'score' => 95.00,
        ]);
    }

    public function test_cross_three_surahs_hafalan_splits_into_three_records()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah1->id,
            'surah_end_id' => $this->surah3->id,
            'ayah_start' => 6,
            'ayah_end' => 15,
            'submission_type' => 'continuation',
            'status' => 'passed',
            'score' => 88,
            'submitted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));

        // Should create 3 records:
        // Record 1: Surah 1, Ayah 6 to 7
        // Record 2: Surah 2, Ayah 1 to 286 (entire Surah 2)
        // Record 3: Surah 3, Ayah 1 to 15
        $this->assertDatabaseCount('hafalan_records', 3);

        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah1->id,
            'ayah_start' => 6,
            'ayah_end' => 7,
        ]);

        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 286,
        ]);

        $this->assertDatabaseHas('hafalan_records', [
            'surah_id' => $this->surah3->id,
            'ayah_start' => 1,
            'ayah_end' => 15,
        ]);
    }

    public function test_cross_surah_murajaah_splits_into_multiple_records()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah1->id,
            'surah_end_id' => $this->surah2->id,
            'ayah_start' => 6,
            'ayah_end' => 20,
            'fluency_score' => 90,
            'tajwid_score' => 80,
            'makhraj_score' => 85,
            'status' => 'passed',
            'reviewed_at' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('murajaah-records.index'));

        // Should create 2 records:
        // Record 1: Surah 1, Ayah 6 to 7
        // Record 2: Surah 2, Ayah 1 to 20
        $this->assertDatabaseCount('murajaah_records', 2);

        $this->assertDatabaseHas('murajaah_records', [
            'surah_id' => $this->surah1->id,
            'ayah_start' => 6,
            'ayah_end' => 7,
            'fluency_score' => 90,
            'overall_score' => 85, // avg of 90, 80, 85
        ]);

        $this->assertDatabaseHas('murajaah_records', [
            'surah_id' => $this->surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 20,
            'fluency_score' => 90,
            'overall_score' => 85,
        ]);
    }

    public function test_validation_fails_if_end_surah_is_before_start_surah()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah2->id,
            'surah_end_id' => $this->surah1->id, // Invalid: Al-Fatihah is before Al-Baqarah
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['surah_end_id']);
        $this->assertDatabaseEmpty('hafalan_records');
    }

}
