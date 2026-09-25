<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Jurnal Pembelajaran kelas Reguler di Laporan Triwulan: satu baris per hari
 * pertemuan aktif kelas (jadwal kelas x kalender), bukan "Pekan 1-5".
 */
class QuarterlyReportJurnalTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function julyJurnal(ClassRoom $classRoom): array
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $classRoom->id, 'academic_year' => '2026/2027', 'term' => '1',
        ]));
        $response->assertOk();

        return $response->viewData('halaqahData')[0]['monthly']['07']['jurnal'];
    }

    private function regulerClass(array $days, string $frequency = 'setiap hari'): ClassRoom
    {
        $program = Program::create(['name' => 'Program Reguler Jurnal', 'status' => 'active', 'meeting_frequency' => $frequency]);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Jurnal', 'level' => 'XI', 'tahfizh_days' => $days]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        return $classRoom;
    }

    #[Test]
    public function reguler_jurnal_lists_every_active_meeting_day_with_day_name(): void
    {
        // Senin & Rabu. Juli 2026: Senin 6,13,20,27 & Rabu 1,8,15,22,29.
        $classRoom = $this->regulerClass([1, 3]);
        Attendance::create([
            'student_id' => $this->student->id, 'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-07-06', 'status' => 'hadir',
        ]);

        $jurnal = $this->julyJurnal($classRoom);

        $this->assertSame([
            'Rabu, 01-07-2026', 'Senin, 06-07-2026', 'Rabu, 08-07-2026', 'Senin, 13-07-2026', 'Rabu, 15-07-2026',
            'Senin, 20-07-2026', 'Rabu, 22-07-2026', 'Senin, 27-07-2026', 'Rabu, 29-07-2026',
        ], array_column($jurnal, 'tanggal'));

        $held = collect($jurnal)->firstWhere('tanggal', 'Senin, 06-07-2026');
        $this->assertSame(1, $held['jumlah_murid']);
        $this->assertSame('✓', $held['paraf']);

        $notYet = collect($jurnal)->firstWhere('tanggal', 'Rabu, 08-07-2026');
        $this->assertNull($notYet['jumlah_murid']);
        $this->assertSame('-', $notYet['paraf']);

        $this->assertFalse(collect($jurnal)->contains(fn ($row) => str_starts_with($row['tanggal'], 'Pekan')));
    }

    #[Test]
    public function weekly_program_has_one_meeting_per_week_preferring_the_day_with_data(): void
    {
        // Jadwal Selasa & Kamis, tapi program seminggu sekali.
        $classRoom = $this->regulerClass([2, 4], 'seminggu sekali');
        Attendance::create([
            'student_id' => $this->student->id, 'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-07-09', 'status' => 'hadir', // Kamis pekan ke-2
        ]);

        $dates = array_column($this->julyJurnal($classRoom), 'tanggal');

        // Juli 2026 per pekan ISO: Kam 2 | Sel 7 -> Kam 9 (ada data) | Sel 14 | Sel 21 | Sel 28
        $this->assertSame(
            ['Kamis, 02-07-2026', 'Kamis, 09-07-2026', 'Selasa, 14-07-2026', 'Selasa, 21-07-2026', 'Selasa, 28-07-2026'],
            $dates
        );
    }
}
