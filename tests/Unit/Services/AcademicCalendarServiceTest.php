<?php

namespace Tests\Unit\Services;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademicCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AcademicCalendarService;
    }

    #[Test]
    public function term_start_date_matches_the_correct_quarter(): void
    {
        $this->assertSame('2026-07-01', $this->service->termStartDate(Carbon::parse('2026-09-15'))->toDateString());
        $this->assertSame('2026-10-01', $this->service->termStartDate(Carbon::parse('2026-11-20'))->toDateString());
        $this->assertSame('2027-01-01', $this->service->termStartDate(Carbon::parse('2027-02-10'))->toDateString());
        $this->assertSame('2026-04-01', $this->service->termStartDate(Carbon::parse('2026-05-05'))->toDateString());
    }

    #[Test]
    public function daily_program_counts_only_configured_weekdays_and_skips_holidays(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X UMMI',
            'level' => 'X',
            'tahfizh_days' => [1, 2, 3, 4], // Senin-Kamis
        ]);

        // Term dimulai Rabu, 2026-07-01.
        $this->assertSame(1, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-01')));
        // Kamis: pertemuan ke-2.
        $this->assertSame(2, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-02')));
        // Jumat-Minggu (07-03 s/d 07-05) bukan hari efektif, tidak menambah hitungan
        // untuk Senin berikutnya (07-06): tetap pertemuan ke-3.
        $this->assertSame(3, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-06')));
        $this->assertSame(4, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-07')));

        // Pertemuan susulan di hari libur jadwal (Jumat, 07-03) tetap dihitung
        // sebagai satu pertemuan nyata: 2 hari efektif sebelumnya (07-01, 07-02) + 1.
        $this->assertSame(3, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-03')));

        // Tandai 2026-07-07 (Selasa, hari efektif) sebagai libur nasional.
        $this->markHoliday('2026-07-07');

        // 07-08 (Rabu): tanpa libur harusnya pertemuan ke-5, dengan libur di 07-07
        // yang dilewati, jadi tetap pertemuan ke-4.
        $this->assertSame(4, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-08')));
    }

    #[Test]
    public function ummi_excludes_friday_even_when_class_is_active_that_day(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X UMMI',
            'level' => 'X',
            // Hari aktif kelas Senin-Jumat (Jumat dipakai tahfizh mandiri, non-UMMI).
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);

        // Jumat, 2026-07-03: hari aktif kelas, tapi BUKAN hari efektif UMMI.
        $this->assertTrue($this->service->isEffectiveDay($classRoom, Carbon::parse('2026-07-03')));
        $this->assertFalse($this->service->isEffectiveDay($classRoom, Carbon::parse('2026-07-03'), forUmmi: true));

        // Term mulai Rabu 07-01: hari efektif UMMI di pekan pertama cuma Rabu &
        // Kamis (07-01, 07-02) -- Jumat (07-03) dilewati, jadi Senin berikutnya
        // (07-06) tetap pertemuan UMMI ke-3, bukan ke-4.
        $this->assertSame(3, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-06'), forUmmi: true));

        // Tanpa forUmmi (mis. dipakai program lain), Jumat tetap dihitung
        // sebagai hari aktif kelas: Senin (07-06) jadi pertemuan ke-4.
        $this->assertSame(4, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-06')));
    }

    #[Test]
    public function class_specific_holiday_is_skipped_only_for_that_class(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classA = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X A',
            'level' => 'X',
            'tahfizh_days' => [1, 2, 3, 4],
        ]);
        $classB = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X B',
            'level' => 'X',
            'tahfizh_days' => [1, 2, 3, 4],
        ]);

        // Libur sebagian: hanya Kelas A yang libur di 2026-07-02.
        $this->markClassHoliday('2026-07-02', $classA->id);

        $this->assertFalse($this->service->isEffectiveDay($classA, Carbon::parse('2026-07-02')));
        $this->assertTrue($this->service->isEffectiveDay($classB, Carbon::parse('2026-07-02')));
    }

    #[Test]
    public function weekly_program_counts_at_most_one_meeting_per_calendar_week(): void
    {
        $program = Program::create([
            'name' => 'Program Reguler Mingguan',
            'meeting_frequency' => 'seminggu sekali',
            'status' => 'active',
        ]);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Reguler Mingguan',
            'level' => 'XI',
            'tahfizh_days' => [3], // Jadwal kelas: Rabu saja.
        ]);

        $this->assertSame(1, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-01'))); // Rabu pekan 1
        $this->assertSame(2, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-08'))); // Rabu pekan 2
        $this->assertSame(3, $this->service->tatapMukaNumber($classRoom, Carbon::parse('2026-07-15'))); // Rabu pekan 3
    }
}
