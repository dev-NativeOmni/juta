<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Regresi untuk bug: kolom Ziyadah di tampilan Progress Kelas 10 memakai
 * HafalanRecord::where('status', ...) langsung ke header, padahal kolom
 * status sudah pindah ke hafalan_record_surahs sejak header/detail split --
 * di SQLite ini diam-diam mengembalikan null alih-alih error.
 */
class ProgressReportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function grade_10_progress_view_shows_ziyadah_name_from_latest_passed_hafalan(): void
    {
        $program = Program::create([
            'name' => 'Tahfizh Reguler Kelas X',
            'status' => 'active',
        ]);

        $classRoomX = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X Test',
            'level' => 'X',
        ]);

        $this->student->update(['class_room_id' => $classRoomX->id]);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);

        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('progress.index', [
            'class_room_id' => $classRoomX->id,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('isGrade10', true);
        $response->assertSee($this->surah->name_latin);
    }
}
