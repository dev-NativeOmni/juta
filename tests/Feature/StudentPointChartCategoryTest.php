<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Laporan Tanse (student-points.chart) bisa menampilkan Pelanggaran atau
 * Penghargaan/Prestasi, lengkap dengan sub-jenisnya.
 */
class StudentPointChartCategoryTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private User $tanse;

    private Student $achiever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $role = Role::firstOrCreate(['name' => 'tanse'], ['display_name' => 'Ketahanan Sekolah']);
        $this->tanse = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $program = Program::create(['name' => 'Program Tanse', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Tanse', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $classRoom->id, 'name' => 'Murid Telat']);
        $this->achiever = Student::create([
            'class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid Juara',
            'student_number' => 'TNS-001', 'gender' => 'female', 'birth_date' => '2009-01-01', 'status' => 'active',
        ]);

        $point = fn (Student $s, string $type, int $pts, string $title, ?string $achievementType = null) => StudentPoint::create([
            'student_id' => $s->id, 'type' => $type, 'points' => $pts, 'title' => $title,
            'achievement_type' => $achievementType, 'date' => '2026-09-10', 'logged_by' => $this->admin->id,
        ]);
        $point($this->student, 'lateness', 5, 'Terlambat Apel');
        $point($this->achiever, 'reward', 30, 'Juara 1 Olimpiade Matematika', 'academic');
        $point($this->achiever, 'reward', 20, 'Juara 2 Futsal', 'non-academic');
    }

    private function chart(array $query)
    {
        return $this->actingAs($this->tanse)->get(route('student-points.chart', ['year' => 2026, 'month' => 9] + $query));
    }

    #[Test]
    public function default_view_still_shows_violations_only(): void
    {
        $response = $this->chart([]);

        $response->assertOk();
        $response->assertViewHas('category', 'violation');
        $this->assertSame(['Murid Telat'], $response->viewData('studentLeaderboard')->pluck('student.name')->all());
        $this->assertSame(1, $response->viewData('monthViolationsCount'));
    }

    #[Test]
    public function reward_view_ranks_achievements_with_their_breakdown(): void
    {
        $response = $this->chart(['category' => 'reward']);

        $response->assertOk();
        $leaderboard = $response->viewData('studentLeaderboard');
        $this->assertSame(['Murid Juara'], $leaderboard->pluck('student.name')->all());
        $this->assertSame(50, $leaderboard->first()['violation_points']);
        $this->assertSame(['academic' => 1, 'non-academic' => 1, 'other' => 0], $leaderboard->first()['type_counts']);
        $this->assertSame(2, $response->viewData('monthViolationsCount'));
        $response->assertSee('Penghargaan / Prestasi');
        $response->assertSee('Juara 1 Olimpiade Matematika');
        $response->assertDontSee('Terlambat Apel');
    }

    #[Test]
    public function reward_sub_type_filter_narrows_the_ranking(): void
    {
        $response = $this->chart(['category' => 'reward', 'violation_type' => 'non-academic']);

        $this->assertSame(20, $response->viewData('studentLeaderboard')->first()['violation_points']);
    }

    #[Test]
    public function a_violation_sub_type_is_ignored_in_reward_view(): void
    {
        $response = $this->chart(['category' => 'reward', 'violation_type' => 'lateness']);

        $response->assertViewHas('violationType', 'all');
        $this->assertSame(50, $response->viewData('studentLeaderboard')->first()['violation_points']);
    }
}
