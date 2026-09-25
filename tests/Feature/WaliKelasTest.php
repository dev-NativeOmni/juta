<?php

namespace Tests\Feature;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Dashboard pemantauan Wali Kelas: hanya melihat murid kelas yang diampu (satu wali
 * kelas satu kelas), belum tuntas hafalan per bulan & triwulan, kuisioner adab yang
 * belum diisi hari ini, dan rekap kedisiplinan bulan berjalan.
 */
class WaliKelasTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private User $waliKelasUser;

    private ClassRoom $classRoom;

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
    }

    #[Test]
    public function wali_kelas_role_exists_after_migration(): void
    {
        $this->assertNotNull(Role::where('name', 'wali_kelas')->first());
    }

    #[Test]
    public function guest_and_other_roles_cannot_view_the_dashboard(): void
    {
        $this->get(route('wali-kelas.index'))->assertRedirect('/login');
        $this->actingAs($this->teacherUser)->get(route('wali-kelas.index'))->assertStatus(403);
    }

    #[Test]
    public function wali_kelas_without_an_assigned_class_sees_an_empty_state(): void
    {
        $unassigned = User::factory()->create([
            'role_id' => Role::where('name', 'wali_kelas')->first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($unassigned)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertSee('belum ditugaskan sebagai wali kelas');
    }

    #[Test]
    public function dashboard_only_shows_students_from_the_assigned_class(): void
    {
        $otherClass = ClassRoom::create([
            'program_id' => $this->classRoom->program_id,
            'name' => 'Kelas XII F2',
            'level' => 'XII',
        ]);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-099',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertSee($this->student->name);
        $response->assertDontSee($otherStudent->name);
    }

    #[Test]
    public function student_below_target_is_listed_as_not_tuntas_for_the_month(): void
    {
        Carbon::setTestNow('2026-07-15');
        $this->classRoom->update(['tahfizh_days' => [3]]); // Rabu saja, sedikit pertemuan.

        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertViewHas('monthlyTuntas', function ($monthly) {
            $julyRows = $monthly['2026-07']['rows'];

            return $julyRows->firstWhere(fn ($r) => $r['student']->id === $this->student->id) !== null
                && $monthly['2026-07']['label'] === 'Juli';
        });
        $response->assertViewHas('termTuntas', fn ($term) => $term['label'] === 'Juli - September');
        $response->assertSee('Juli - September');

        Carbon::setTestNow();
    }

    #[Test]
    public function student_who_reaches_the_target_position_is_not_listed(): void
    {
        Carbon::setTestNow('2026-09-25');

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 7,
            'target_date' => '2026-07-31',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertViewHas('monthlyTuntas', function ($monthly) {
            $julyRows = $monthly['2026-07']['rows'];

            return $julyRows->firstWhere(fn ($r) => $r['student']->id === $this->student->id) === null;
        });

        Carbon::setTestNow();
    }

    #[Test]
    public function adab_section_lists_students_missing_todays_questionnaire(): void
    {
        // Selasa, hari efektif kuisioner adab.
        Carbon::setTestNow('2026-07-07');

        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertViewHas('adabToday', function ($adabToday) {
            return $adabToday['is_effective_day'] === true
                && $adabToday['missing']->contains(fn ($s) => $s->id === $this->student->id);
        });
        $response->assertSee($this->student->name);

        AdabRecord::create([
            'student_id' => $this->student->id,
            'evaluator_id' => $this->teacherUser->id,
            'assessment_date' => '2026-07-07',
            'answers' => [],
            'total_score' => 100,
        ]);

        $response2 = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));
        $response2->assertViewHas('adabToday', function ($adabToday) {
            return $adabToday['missing']->isEmpty();
        });

        Carbon::setTestNow();
    }

    #[Test]
    public function discipline_section_summarizes_this_months_violations(): void
    {
        Carbon::setTestNow('2026-07-15');

        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => 'violation',
            'points' => 15,
            'title' => 'Terlambat masuk kelas',
            'date' => '2026-07-10',
            'logged_by' => $this->teacherUser->id,
        ]);

        $response = $this->actingAs($this->waliKelasUser)->get(route('wali-kelas.index'));

        $response->assertStatus(200);
        $response->assertViewHas('discipline', function ($discipline) {
            $row = $discipline['rows']->firstWhere(fn ($r) => $r['student']->id === $this->student->id);

            return $row['total_points'] === 15 && $row['count'] === 1 && $row['grade'] === 'B';
        });
        $response->assertSee('Terlambat masuk kelas');

        Carbon::setTestNow();
    }

    #[Test]
    public function admin_can_assign_a_wali_kelas_when_creating_a_class(): void
    {
        $newWaliKelas = User::factory()->create([
            'role_id' => Role::where('name', 'wali_kelas')->first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('class-rooms.store'), [
            'program_id' => $this->classRoom->program_id,
            'name' => 'Kelas XII F5',
            'level' => 'XII',
            'wali_kelas_user_id' => $newWaliKelas->id,
        ]);

        $response->assertRedirect(route('class-rooms.index'));
        $this->assertDatabaseHas('class_rooms', [
            'name' => 'Kelas XII F5',
            'wali_kelas_user_id' => $newWaliKelas->id,
        ]);
    }

    #[Test]
    public function class_room_form_enforces_one_class_per_wali_kelas(): void
    {
        $otherClass = ClassRoom::create([
            'program_id' => $this->classRoom->program_id,
            'name' => 'Kelas XII F2',
            'level' => 'XII',
        ]);

        $response = $this->actingAs($this->admin)->put(route('class-rooms.update', $otherClass), [
            'program_id' => $otherClass->program_id,
            'name' => $otherClass->name,
            'level' => $otherClass->level,
            'wali_kelas_user_id' => $this->waliKelasUser->id,
        ]);

        $response->assertSessionHasErrors('wali_kelas_user_id');
        $this->assertNull($otherClass->fresh()->wali_kelas_user_id);
    }
}
