<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tab Term / Indeks: bila ada target posisi, TUNTAS hanya jika capaian sudah sampai
 * posisi target -- jumlah baris yang cukup saja tidak membuat tuntas.
 */
class QuarterlyTermTuntasTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286]);
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        // Satu pertemuan per pekan (Rabu): target baris kecil, mudah terlampaui.
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F3', 'level' => 'XI', 'tahfizh_days' => [3]]);
        // Sudah pindah ke depan (Juz 1 -> 2), jadi Al-Baqarah 120 (Juz 1) sebelum Al-Baqarah 200 (Juz 2).
        $this->student->update([
            'class_room_id' => $this->classRoom->id, 'teacher_id' => $this->teacherProfile->id,
            'tahfizh_level' => 'tahsin', 'hafalan_direction' => 'front_29',
        ]);

        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-07-08']);
        $record->surahs()->create([
            'surah_id' => $this->baqarah->id, 'ayah_start' => 1, 'ayah_end' => 120, 'submission_type' => 'new', 'status' => 'passed',
        ]);
    }

    private function termRecord(): array
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1',
        ]));
        $response->assertOk();

        return $response->viewData('halaqahData')[0]['term_records'][0];
    }

    private function target(int $ayah): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->baqarah->id, 'ayah' => $ayah, 'target_date' => '2026-09-30', 'status' => 'active',
        ]);
    }

    #[Test]
    public function enough_lines_but_target_position_not_reached_is_not_tuntas(): void
    {
        $this->target(200);

        $record = $this->termRecord();

        $this->assertGreaterThanOrEqual($record['target_lines'], $record['total_lines'], 'Baris sudah melebihi target baris.');
        $this->assertFalse($record['is_tuntas'], 'Capaian ayat 120 belum sampai target ayat 200.');
    }

    #[Test]
    public function reaching_the_target_position_is_tuntas(): void
    {
        $this->target(100);

        $this->assertTrue($this->termRecord()['is_tuntas']);
    }
}
