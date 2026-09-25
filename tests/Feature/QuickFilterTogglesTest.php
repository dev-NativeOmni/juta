<?php

namespace Tests\Feature;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TahfizhExam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Filter cepat sekali-klik (pill di tablet/desktop, dropdown di handphone) yang
 * ditambahkan di Riwayat Setoran, Murojaah, Target Hafalan, Ujian Tahfizh,
 * Kedisiplinan, dan Kuisioner Adab -- lewat komponen <x-filter-toggle>.
 */
class QuickFilterTogglesTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    private Student $secondStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F1',
            'level' => 'XII',
        ]);
        $this->student->update(['class_room_id' => $this->classRoom->id]);

        $this->secondStudent = Student::create([
            'class_room_id' => ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas XII F2', 'level' => 'XII'])->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kedua',
            'student_number' => 'TEST-SNT-601',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function filter_toggle_component_renders_a_pill_for_desktop_and_a_select_for_mobile(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-records.index'));

        $response->assertStatus(200);
        $response->assertSee('hidden sm:flex', false);
        $response->assertSee('sm:hidden w-full', false);
    }

    #[Test]
    public function hafalan_record_status_filter_narrows_the_list(): void
    {
        // Filter status di controller bekerja per header (whereHas ada surat berstatus X),
        // jadi tiap header di sini sengaja dibuat 1 surat saja supaya hasilnya bersih.
        $passedRecord = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $passedRecord->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 5,
            'submission_type' => 'new', 'status' => 'passed', 'score' => 90,
        ]);

        $repeatRecord = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $repeatRecord->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 6, 'ayah_end' => 7,
            'submission_type' => 'new', 'status' => 'repeat', 'score' => 50,
        ]);

        $passed = $this->actingAs($this->admin)->get(route('hafalan-records.index', ['status' => 'passed']));
        $passed->assertStatus(200);
        $rows = $passed->viewData('hafalanRecords');
        $this->assertSame([$passedRecord->id], $rows->pluck('id')->all());

        $repeat = $this->actingAs($this->admin)->get(route('hafalan-records.index', ['status' => 'repeat']));
        $rows = $repeat->viewData('hafalanRecords');
        $this->assertSame([$repeatRecord->id], $rows->pluck('id')->all());
    }

    #[Test]
    public function murajaah_class_and_status_filters_narrow_the_list(): void
    {
        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'score' => 90,
            'reviewed_at' => now(),
        ]);
        MurajaahRecord::create([
            'student_id' => $this->secondStudent->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'repeat',
            'score' => 40,
            'reviewed_at' => now(),
        ]);

        $byClass = $this->actingAs($this->admin)->get(route('murajaah-records.index', ['class_room_id' => $this->classRoom->id]));
        $rows = $byClass->viewData('murajaahRecords');
        $this->assertTrue($rows->every(fn ($r) => $r->student_id === $this->student->id));
        $this->assertTrue($rows->isNotEmpty());

        $byStatus = $this->actingAs($this->admin)->get(route('murajaah-records.index', ['status' => 'repeat']));
        $rows = $byStatus->viewData('murajaahRecords');
        $this->assertTrue($rows->every(fn ($r) => $r->status === 'repeat'));
        $this->assertTrue($rows->isNotEmpty());
    }

    #[Test]
    public function hafalan_target_status_filter_narrows_the_list(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 5, 'target_date' => now(), 'status' => 'active',
        ]);
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 3, 'target_date' => now()->subDay(), 'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.index', ['status' => 'completed']));
        $response->assertStatus(200);
        $rows = $response->viewData('targets');
        $this->assertTrue($rows->every(fn ($t) => $t->status === 'completed'));
        $this->assertTrue($rows->isNotEmpty());
    }

    #[Test]
    public function hafalan_target_class_filter_narrows_the_list(): void
    {
        $otherStudentTarget = HafalanTarget::create([
            'student_id' => $this->secondStudent->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 5, 'target_date' => now(), 'status' => 'active',
        ]);
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 3, 'target_date' => now(), 'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.index', ['class_room_id' => $this->secondStudent->class_room_id]));

        $response->assertStatus(200);
        $rows = $response->viewData('targets');
        $this->assertSame([$otherStudentTarget->id], $rows->pluck('id')->all());
        $response->assertSee('hidden sm:flex', false);
        $response->assertSee('sm:hidden w-full', false);
    }

    #[Test]
    public function class_filter_pills_link_to_the_real_class_id_not_the_list_position(): void
    {
        // Regresi: collect([...])->merge() menomori ulang key angka, sehingga pill
        // "Kelas XII F2" menunjuk ke class_room_id=1 (posisi) dan hasilnya kosong.
        $secondClassId = $this->secondStudent->class_room_id;

        foreach (['hafalan-targets.index', 'murajaah-records.index'] as $routeName) {
            $response = $this->actingAs($this->admin)->get(route($routeName));

            $response->assertOk();
            $this->assertMatchesRegularExpression(
                '/class_room_id='.$secondClassId.'"[^>]*>\s*Kelas XII F2\s*</',
                $response->getContent(),
                "Pill kelas di {$routeName} harus memakai id kelas yang asli."
            );
        }
    }

    #[Test]
    public function tahfizh_exam_pass_status_filter_uses_the_scoring_threshold(): void
    {
        TahfizhExam::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'juz' => 30, 'total_score' => 35, 'exam_date' => now(),
        ]);
        TahfizhExam::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'juz' => 29, 'total_score' => 15, 'exam_date' => now(),
        ]);

        $lulus = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['pass_status' => 'lulus']));
        $lulus->assertStatus(200);
        $threshold = $lulus->viewData('passThreshold');
        $rows = $lulus->viewData('exams');
        $this->assertTrue($rows->isNotEmpty());
        $this->assertTrue($rows->every(fn ($e) => (float) $e->total_score >= $threshold));

        $tidakLulus = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['pass_status' => 'tidak_lulus']));
        $rows = $tidakLulus->viewData('exams');
        $this->assertTrue($rows->isNotEmpty());
        $this->assertTrue($rows->every(fn ($e) => (float) $e->total_score < $threshold));
    }

    #[Test]
    public function student_points_type_filter_narrows_the_list(): void
    {
        StudentPoint::create([
            'student_id' => $this->student->id, 'type' => 'violation', 'points' => 10,
            'title' => 'Terlambat masuk', 'date' => now()->toDateString(), 'logged_by' => $this->teacherUser->id,
        ]);
        StudentPoint::create([
            'student_id' => $this->student->id, 'type' => 'reward', 'points' => 5,
            'title' => 'Hafalan lancar', 'date' => now()->toDateString(), 'logged_by' => $this->teacherUser->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('student-points.index', ['type' => 'reward']));
        $response->assertStatus(200);
        $response->assertSee('Hafalan lancar');
        $response->assertDontSee('Terlambat masuk');
    }

    #[Test]
    public function adab_fill_status_filter_narrows_students_by_todays_submission(): void
    {
        AdabRecord::create([
            'student_id' => $this->student->id,
            'evaluator_id' => $this->teacherUser->id,
            'assessment_date' => now()->toDateString(),
            'answers' => [],
            'total_score' => 100,
        ]);

        $belum = $this->actingAs($this->admin)->get(route('adab.index', ['fill_status' => 'belum']));
        $belum->assertStatus(200);
        $names = $belum->viewData('students')->pluck('name');
        $this->assertNotContains($this->student->name, $names);

        $sudah = $this->actingAs($this->admin)->get(route('adab.index', ['fill_status' => 'sudah']));
        $names = $sudah->viewData('students')->pluck('name');
        $this->assertContains($this->student->name, $names);
    }
}
