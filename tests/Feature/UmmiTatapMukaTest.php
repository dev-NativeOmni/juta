<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * TM (tatap muka) UMMI sekarang dihitung dari kalender hari efektif kelas
 * (AcademicCalendarService), bukan lagi riwayat "TM terakhir murid + 1".
 */
class UmmiTatapMukaTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function grade10ClassRoom(): ClassRoom
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);

        return ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X UMMI',
            'level' => 'X',
            // Hari aktif kelas Senin-Jumat (Jumat dipakai tahfizh mandiri,
            // bukan UMMI) -- endpoint & backfill harus tetap mengecualikan
            // Jumat khusus untuk hitungan TM UMMI.
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
    }

    #[Test]
    public function suggestion_endpoint_returns_tatap_muka_from_calendar(): void
    {
        $classRoom = $this->grade10ClassRoom();

        $response = $this->actingAs($this->teacherUser)->get(route('ummi-records.tatap-muka-suggestion', [
            'class_room_id' => $classRoom->id,
            'date' => '2026-07-06', // Senin pekan ke-2 term 1, pertemuan ke-3 (Jumat 07-03 dilewati).
        ]));

        $response->assertStatus(200);
        $response->assertJson(['tatap_muka' => 3]);
    }

    #[Test]
    public function suggestion_endpoint_requires_valid_class_and_date(): void
    {
        $response = $this->actingAs($this->teacherUser)->getJson(route('ummi-records.tatap-muka-suggestion', [
            'class_room_id' => 999999,
            'date' => '2026-07-06',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('class_room_id');
    }

    #[Test]
    public function backfill_migration_recalculates_existing_tatap_muka_from_calendar(): void
    {
        $classRoom = $this->grade10ClassRoom();
        $this->student->update(['class_room_id' => $classRoom->id]);

        $record = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'tatap_muka' => 999, // Nilai lama yang salah (hasil hitungan manual lama).
        ]);

        $migration = require database_path('migrations/2026_09_16_000001_recalculate_ummi_tatap_muka_from_academic_calendar.php');
        $migration->up();

        $this->assertSame(3, $record->fresh()->tatap_muka);
    }
}
