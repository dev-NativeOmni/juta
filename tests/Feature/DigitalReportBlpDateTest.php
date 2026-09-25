<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Titimangsa rapor memakai Tanggal BLP dari Pengaturan Rapor: ASTS untuk triwulan
 * pertama semester, ASAS (sem. 1) / ASAT (sem. 2) untuk triwulan kedua.
 */
class DigitalReportBlpDateTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function saveBlp(array $dates): void
    {
        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2026/2027', 'semester' => 1, 'blp_dates' => $dates,
            'report_main_title' => 'LAPORAN', 'report_school_name' => 'SMA', 'report_city' => 'Sukoharjo',
        ])->assertRedirect();
    }

    private function printed(int $semester, int $term)
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [
            $this->student, 'academic_year' => '2026/2027', 'semester' => $semester, 'term' => $term,
        ]));
    }

    #[Test]
    public function each_triwulan_uses_its_own_blp_date(): void
    {
        $this->saveBlp(['1_asts' => '2026-10-10', '1_asas' => '2026-12-19', '2_asts' => '2027-03-20', '2_asat' => '2027-06-19']);

        $this->printed(1, 1)->assertOk()->assertSee('Sukoharjo, 10 Oktober 2026');
        $this->printed(1, 2)->assertSee('Sukoharjo, 19 Desember 2026');
        $this->printed(2, 3)->assertSee('Sukoharjo, 20 Maret 2027');
        $this->printed(2, 4)->assertSee('Sukoharjo, 19 Juni 2027');
    }

    #[Test]
    public function unset_blp_date_falls_back_to_today(): void
    {
        Carbon::setTestNow('2026-11-05');

        $date = StudentReportController::reportDate('2026/2027', 1, 2);
        $this->assertSame('05 November 2026', $date['date']);
        $this->assertSame('ASAS', $date['exam']);
        $this->assertFalse($date['is_set']);

        Carbon::setTestNow();
    }

    #[Test]
    public function blp_dates_are_stored_per_academic_year(): void
    {
        $this->saveBlp(['1_asts' => '2026-10-10']);

        $this->assertSame('2026-10-10', StudentReportController::blpDates('2026/2027')['1_asts']);
        $this->assertNull(StudentReportController::blpDates('2027/2028')['1_asts']);
        $this->actingAs($this->admin)->get(route('digital-reports.settings'))->assertOk()->assertSee('value="2026-10-10"', false);
    }
}
