<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kolom Target & Capaian di rapor cetak murid UMMI (Kelas 10) harus
 * menampilkan format khusus:
 * - Target: "Ummi : Jilid X Hal Y" + "Tahfizh : Surah X Ayat Y"
 * - Capaian: "Ummi : ..." + "Tahfizh Ummi : ..." (hafalan di dalam sesi
 *   UMMI) + "Tahfizh Mandiri : ..." (setoran hafalan terpisah)
 * bukan format "QS. X (Ayat Y)" tunggal yang dipakai untuk target Reguler
 * murni.
 */
class DigitalReportUmmiTargetCapaianTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function ummi_target_row_shows_separate_ummi_and_tahfizh_lines(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'ummi',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 2',
            'halaman_buku' => '24-25',
            'surah_id' => $this->surah->id,
            'ayah' => null,
            'target_date' => now(),
            'status' => 'active',
        ]);

        // Hafalan yang dicatat di dalam sesi UMMI itu sendiri ("Tahfizh Ummi").
        $anNaba = Surah::firstOrCreate(
            ['number' => 78],
            ['name_ar' => 'النبأ', 'name_latin' => 'An-Naba', 'total_ayah' => 40, 'juz_start' => 30, 'juz_end' => 30]
        );
        $ummiRecord = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => now(),
            'tatap_muka' => 1,
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman' => '24-25',
            'nilai' => 'B',
        ]);
        $ummiRecord->surahs()->create([
            'surah_id' => $anNaba->id,
            'hafalan_ayah' => '1-5',
        ]);

        // Setoran hafalan mandiri/terpisah dari sesi UMMI ("Tahfizh Mandiri").
        $anNas = Surah::firstOrCreate(
            ['number' => 114],
            ['name_ar' => 'الناس', 'name_latin' => 'An-Nas', 'total_ayah' => 6, 'juz_start' => 30, 'juz_end' => 30]
        );
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $record->surahs()->create([
            'surah_id' => $anNas->id,
            'ayah_start' => 1,
            'ayah_end' => 6,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('digital-reports.print', $this->student));

        $response->assertStatus(200);
        // Target: halaman disimpan sebagai rentang ("24-25") tapi rapor
        // cukup menampilkan angka halaman terakhirnya saja.
        $response->assertSee('Ummi : Jilid 2 Hal 25', false);
        $response->assertSee('Tahfizh : Surah '.$this->surah->name_latin, false);

        // Capaian: tiga baris terpisah, Ummi + Tahfizh Ummi + Tahfizh Mandiri.
        $response->assertSee('Tahfizh Ummi : Surah An-Naba Ayat 5', false);
        $response->assertSee('Tahfizh Mandiri : Surah An-Nas Ayat 6', false);
    }

    #[Test]
    public function reguler_only_target_row_keeps_the_single_line_format(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 10,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('digital-reports.print', $this->student));

        $response->assertStatus(200);
        $response->assertSee('QS. '.$this->surah->name_latin.' (Ayat 1 - 10)', false);
        $response->assertDontSee('Ummi : Jilid');
    }
}
