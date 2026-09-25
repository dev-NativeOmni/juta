<?php

namespace Tests\Unit\Services;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class StudentProgressServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private StudentProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->service = $this->app->make(StudentProgressService::class);
    }

    private function createHafalanRecord(array $overrides = []): HafalanRecord
    {
        $record = HafalanRecord::create([
            'student_id' => $overrides['student_id'] ?? $this->student->id,
            'teacher_id' => $overrides['teacher_id'] ?? $this->teacherProfile->id,
            'submitted_at' => $overrides['submitted_at'] ?? now(),
        ]);

        $record->surahs()->create([
            'surah_id' => $overrides['surah_id'] ?? $this->surah->id,
            'ayah_start' => $overrides['ayah_start'] ?? 1,
            'ayah_end' => $overrides['ayah_end'] ?? 7,
            'submission_type' => $overrides['submission_type'] ?? 'new',
            'status' => $overrides['status'] ?? 'passed',
            'score' => $overrides['score'] ?? null,
        ]);

        return $record;
    }

    #[Test]
    public function it_returns_zero_progress_when_no_records_exist(): void
    {
        $progress = $this->service->calculate($this->student);

        $this->assertEquals(0, $progress['memorized_ayahs']);
        $this->assertEquals(0, $progress['progress_percent']);
        $this->assertEquals(0, $progress['total_hafalan_records']);
        $this->assertEquals(0, $progress['total_murajaah_records']);
        $this->assertEquals(0, $progress['average_hafalan_score']);
        $this->assertEquals(0, $progress['average_murajaah_score']);
    }

    #[Test]
    public function it_correctly_calculates_memorized_ayahs_for_single_non_overlapping_record(): void
    {
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'score' => 80,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        $this->assertEquals(3, $progress['memorized_ayahs']);
        $this->assertEquals(80, $progress['average_hafalan_score']);
        $this->assertEquals(1, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_merges_overlapping_ayah_ranges_in_same_surah(): void
    {
        // Setoran 1: Ayat 1 - 3
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'score' => 80,
            'submitted_at' => now()->subDay(),
        ]);

        // Setoran 2: Ayat 2 - 5 (overlapping dengan setoran 1)
        $this->createHafalanRecord([
            'ayah_start' => 2,
            'ayah_end' => 5,
            'status' => 'passed',
            'score' => 90,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Gabungan interval [1, 3] dan [2, 5] adalah [1, 5] = 5 ayat
        $this->assertEquals(5, $progress['memorized_ayahs']);
        $this->assertEquals(85, $progress['average_hafalan_score']);
        $this->assertEquals(2, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_does_not_count_non_passed_records_for_progress(): void
    {
        // Setoran 1: Ayat 1 - 3, status repeat (gagal)
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'repeat',
            'score' => 50,
            'submitted_at' => now()->subDay(),
        ]);

        // Setoran 2: Ayat 4 - 7, status passed
        $this->createHafalanRecord([
            'ayah_start' => 4,
            'ayah_end' => 7,
            'status' => 'passed',
            'score' => 88,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Hanya hitung yang 'passed' [4, 7] = 4 ayat
        $this->assertEquals(4, $progress['memorized_ayahs']);
        $this->assertEquals(69, $progress['average_hafalan_score']); // (50 + 88) / 2 = 69
        $this->assertEquals(2, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_calculates_progress_across_multiple_surahs(): void
    {
        // Surah 1 (Al-Fatihah, total 7 ayat): Ayat 1 - 7 (lulus)
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'score' => 95,
            'submitted_at' => now()->subDay(),
        ]);

        // Buat surah kedua: Al-Baqarah (total 286 ayat)
        $surah2 = Surah::create([
            'number' => 2,
            'name_ar' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'juz_start' => 1,
            'juz_end' => 3,
        ]);

        // Setoran Surah 2: Ayat 1 - 10 (lulus)
        $this->createHafalanRecord([
            'surah_id' => $surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'status' => 'passed',
            'score' => 85,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Total memorized: 7 (Al-Fatihah) + 10 (Al-Baqarah) = 17 ayat (All in Juz 1)
        $this->assertEquals(17, $progress['memorized_ayahs']);

        $targetTotalAyahs = $progress['target_total_ayahs'];
        $expectedPercent = round((17 / $targetTotalAyahs) * 100, 2);
        $this->assertEquals($expectedPercent, $progress['progress_percent']);
    }

    #[Test]
    public function it_correctly_calculates_average_murajaah_score(): void
    {
        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'fluency_score' => 90,
            'tajwid_score' => 85,
            'makhraj_score' => 80,
            'overall_score' => 85,
            'reviewed_at' => now(),
        ]);

        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'fluency_score' => 100,
            'tajwid_score' => 95,
            'makhraj_score' => 90,
            'overall_score' => 95,
            'reviewed_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Rata-rata overall_score: (85 + 95) / 2 = 90
        $this->assertEquals(90, $progress['average_murajaah_score']);
        $this->assertEquals(2, $progress['total_murajaah_records']);
    }

    #[Test]
    public function visible_student_query_scopes_wali_kelas_to_their_own_class_only(): void
    {
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas XII F1', 'level' => 'XII']);
        $otherClass = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas XII F2', 'level' => 'XII']);
        $this->student->update(['class_room_id' => $classRoom->id]);

        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-098',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $waliKelasUser = User::factory()->create([
            'role_id' => Role::where('name', 'wali_kelas')->firstOrFail()->id,
            'status' => 'active',
        ]);
        $classRoom->update(['wali_kelas_user_id' => $waliKelasUser->id]);

        $visibleIds = $this->service->visibleStudentQuery($waliKelasUser)->pluck('id')->all();

        $this->assertSame([$this->student->id], $visibleIds);
        $this->assertNotContains($otherStudent->id, $visibleIds);
    }

    #[Test]
    public function visible_student_query_returns_nothing_for_wali_kelas_without_a_class(): void
    {
        $waliKelasUser = User::factory()->create([
            'role_id' => Role::where('name', 'wali_kelas')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->assertCount(0, $this->service->visibleStudentQuery($waliKelasUser)->get());
    }
}
