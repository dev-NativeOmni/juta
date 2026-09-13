<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TahfizhExam;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class TahfizhExamAuthorizationTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected User $otherTeacherUser;

    protected TeacherProfile $otherTeacherProfile;

    protected TahfizhExam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $this->otherTeacherUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->first()->id,
            'name' => 'Guru Lain Test',
            'status' => 'active',
        ]);

        $this->otherTeacherProfile = TeacherProfile::create([
            'user_id' => $this->otherTeacherUser->id,
            'employee_number' => 'TEST-GURU-002',
            'phone' => '081100002222',
        ]);

        $this->exam = TahfizhExam::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'q1' => 80,
            'q2' => 80,
            'q3' => 80,
            'q4' => 80,
            'q5' => 80,
            'total_score' => 80,
            'exam_date' => now()->toDateString(),
        ]);
    }

    #[Test]
    public function owning_teacher_can_edit_and_update_their_own_exam(): void
    {
        $this->actingAs($this->teacherUser)
            ->get(route('tahfizh-exams.edit', $this->exam))
            ->assertStatus(200);

        $response = $this->actingAs($this->teacherUser)->put(route('tahfizh-exams.update', $this->exam), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'type' => 'surah',
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'q1' => 90, 'q2' => 90, 'q3' => 90, 'q4' => 90, 'q5' => 90,
            'notes' => null,
            'exam_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('tahfizh-exams.index'));
        $this->assertEquals(90, $this->exam->fresh()->total_score);
    }

    #[Test]
    public function other_teacher_cannot_edit_or_update_someone_elses_exam(): void
    {
        $this->actingAs($this->otherTeacherUser)
            ->get(route('tahfizh-exams.edit', $this->exam))
            ->assertStatus(403);

        $response = $this->actingAs($this->otherTeacherUser)->put(route('tahfizh-exams.update', $this->exam), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->otherTeacherProfile->id,
            'type' => 'surah',
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'q1' => 10, 'q2' => 10, 'q3' => 10, 'q4' => 10, 'q5' => 10,
            'notes' => null,
            'exam_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertEquals(80, $this->exam->fresh()->total_score);
    }

    #[Test]
    public function other_teacher_cannot_delete_someone_elses_exam(): void
    {
        $response = $this->actingAs($this->otherTeacherUser)
            ->delete(route('tahfizh-exams.destroy', $this->exam));

        $response->assertStatus(403);
        $this->assertDatabaseHas('tahfizh_exams', ['id' => $this->exam->id]);
    }

    #[Test]
    public function admin_can_update_and_delete_any_exam(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tahfizh-exams.edit', $this->exam))
            ->assertStatus(200);

        $response = $this->actingAs($this->admin)
            ->delete(route('tahfizh-exams.destroy', $this->exam));

        $response->assertRedirect(route('tahfizh-exams.index'));
        $this->assertSoftDeleted('tahfizh_exams', ['id' => $this->exam->id]);
    }
}
