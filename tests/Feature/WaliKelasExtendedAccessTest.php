<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
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
 * Wali Kelas diberi akses lihat (bukan input/edit) ke perkembangan tahfizh, adab, dan
 * kedisiplinan murid kelasnya -- termasuk grafik & riwayat -- dengan tetap dibatasi
 * hanya ke kelas yang mereka ampu (tidak boleh melihat data kelas/murid lain).
 */
class WaliKelasExtendedAccessTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private User $waliKelasUser;

    private ClassRoom $classRoom;

    private Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F1',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);

        $waliKelasRole = Role::where('name', 'wali_kelas')->firstOrFail();
        $this->waliKelasUser = User::factory()->create([
            'role_id' => $waliKelasRole->id,
            'status' => 'active',
        ]);
        $this->classRoom->update(['wali_kelas_user_id' => $this->waliKelasUser->id]);

        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler']);

        $otherClass = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F2',
            'level' => 'XII',
        ]);
        $this->otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-501',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function wali_kelas_can_view_but_not_edit_a_students_adab_history(): void
    {
        $response = $this->actingAs($this->waliKelasUser)->get(route('adab.show', $this->student));

        $response->assertStatus(200);
        $response->assertDontSee(route('adab.store-mentor-score', $this->student));
    }

    #[Test]
    public function wali_kelas_cannot_view_adab_history_of_a_student_outside_their_class(): void
    {
        $this->actingAs($this->waliKelasUser)
            ->get(route('adab.show', $this->otherStudent))
            ->assertStatus(403);
    }

    #[Test]
    public function wali_kelas_cannot_access_adab_management_routes(): void
    {
        $this->actingAs($this->waliKelasUser)->get(route('adab.index'))->assertStatus(403);
        $this->actingAs($this->waliKelasUser)->get(route('adab.create', $this->student))->assertStatus(403);
        $this->actingAs($this->waliKelasUser)->post(route('adab.store', $this->student))->assertStatus(403);
    }

    #[Test]
    public function adab_chart_is_scoped_to_the_wali_kelas_own_class_only(): void
    {
        $response = $this->actingAs($this->waliKelasUser)->get(route('adab.chart'));

        $response->assertStatus(200);
        $classNames = $response->viewData('classReport')->pluck('class_room.name');
        $this->assertSame(['Kelas XII F1'], $classNames->all());
    }

    #[Test]
    public function wali_kelas_can_view_student_progress_page_for_their_own_class(): void
    {
        $this->actingAs($this->waliKelasUser)
            ->get(route('progress.show', $this->student))
            ->assertStatus(200);
    }

    #[Test]
    public function wali_kelas_cannot_view_student_progress_page_of_another_class(): void
    {
        $this->actingAs($this->waliKelasUser)
            ->get(route('progress.show', $this->otherStudent))
            ->assertStatus(403);
    }

    #[Test]
    public function reports_periodic_chart_is_scoped_to_the_wali_kelas_class(): void
    {
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ])->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);

        $response = $this->actingAs($this->waliKelasUser)->get(route('reports.periodic'));

        $response->assertStatus(200);
        $classRooms = $response->viewData('classRooms');
        $this->assertSame([$this->classRoom->id], $classRooms->pluck('id')->all());
    }

    #[Test]
    public function reports_student_is_only_visible_for_wali_kelas_own_class(): void
    {
        $this->actingAs($this->waliKelasUser)
            ->get(route('reports.student', $this->student))
            ->assertStatus(200);

        $this->actingAs($this->waliKelasUser)
            ->get(route('reports.student', $this->otherStudent))
            ->assertStatus(403);
    }

    #[Test]
    public function student_points_index_and_chart_are_scoped_to_the_wali_kelas_class(): void
    {
        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => 'violation',
            'points' => 10,
            'title' => 'Terlambat',
            'date' => now()->toDateString(),
            'logged_by' => $this->teacherUser->id,
        ]);
        StudentPoint::create([
            'student_id' => $this->otherStudent->id,
            'type' => 'violation',
            'points' => 50,
            'title' => 'Pelanggaran berat',
            'date' => now()->toDateString(),
            'logged_by' => $this->teacherUser->id,
        ]);

        $indexResponse = $this->actingAs($this->waliKelasUser)->get(route('student-points.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Terlambat');
        $indexResponse->assertDontSee('Pelanggaran berat');
        $indexResponse->assertDontSee(route('student-points.create'));

        $chartResponse = $this->actingAs($this->waliKelasUser)->get(route('student-points.chart'));
        $chartResponse->assertStatus(200);
        $classNames = $chartResponse->viewData('classReport')->pluck('class_room.name');
        $this->assertSame(['Kelas XII F1'], $classNames->all());
    }

    #[Test]
    public function wali_kelas_cannot_manage_student_points(): void
    {
        $this->actingAs($this->waliKelasUser)->get(route('student-points.create'))->assertStatus(403);
    }

    #[Test]
    public function wali_kelas_dashboard_links_to_class_graphs_and_per_student_detail(): void
    {
        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertSee(route('reports.periodic', ['class_room_id' => $this->classRoom->id]), false);
        $response->assertSee(route('adab.chart'), false);
        $response->assertSee(route('student-points.chart', ['class_room_id' => $this->classRoom->id]), false);
        $response->assertSee(route('student-points.index', ['class_room_id' => $this->classRoom->id]), false);
        $response->assertSee(route('progress.show', $this->student), false);
        $response->assertSee(route('adab.show', $this->student), false);
    }

    #[Test]
    public function mobile_bottom_nav_only_shows_routes_wali_kelas_can_actually_open(): void
    {
        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertSee(route('reports.periodic'), false);
        $response->assertSee(route('adab.chart'), false);
        $response->assertSee(route('student-points.chart'), false);
        // Menu default admin (Murid/Hafalan input) yang sebelumnya tampil tapi 403 tidak boleh muncul lagi.
        $response->assertDontSee(route('students.index'), false);
        $response->assertDontSee(route('hafalan-records.index'), false);
    }
}
