<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use App\Models\StudentPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Bagian Tanse di Rapor Digital: poin dibatasi per triwulan, dan deskripsi
 * mengikuti predikat A/B/C dari skor (100 - poin pelanggaran triwulan).
 */
class DigitalReportTanseTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function point(string $type, int $points, string $date, string $title): void
    {
        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => $type,
            'points' => $points,
            'title' => $title,
            'date' => $date,
            'logged_by' => $this->admin->id,
        ]);
    }

    private function report(array $query)
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.show', [$this->student] + $query));
    }

    #[Test]
    public function grade_thresholds_map_to_a_b_c_only(): void
    {
        $this->assertSame('A', StudentReportController::tanseGrade(100));
        $this->assertSame('A', StudentReportController::tanseGrade(90));
        $this->assertSame('B', StudentReportController::tanseGrade(89));
        $this->assertSame('B', StudentReportController::tanseGrade(80));
        $this->assertSame('C', StudentReportController::tanseGrade(79));
        $this->assertSame('C', StudentReportController::tanseGrade(0));
    }

    #[Test]
    public function only_points_inside_the_selected_triwulan_count(): void
    {
        $this->point('violation', 15, '2026-08-10', 'Bolos Triwulan 1');   // T1 2026/2027
        $this->point('lateness', 5, '2026-11-03', 'Terlambat Triwulan 2'); // T2 2026/2027
        $this->point('violation', 40, '2025-08-10', 'Pelanggaran Tahun Lalu');

        $t1 = $this->report(['academic_year' => '2026/2027', 'semester' => 1, 'term' => 1]);
        $t1->assertOk();
        $this->assertSame(85, $t1->viewData('tanseScore'));
        $this->assertSame('B', $t1->viewData('tanseGrade'));
        $this->assertSame(StudentReportController::TANSE_NOTES['B'], $t1->viewData('autoTanseNotes'));
        $t1->assertSee('Bolos Triwulan 1');
        $t1->assertDontSee('Terlambat Triwulan 2');
        $t1->assertDontSee('Pelanggaran Tahun Lalu');

        $t2 = $this->report(['academic_year' => '2026/2027', 'semester' => 1, 'term' => 2]);
        $this->assertSame(95, $t2->viewData('tanseScore'));
        $this->assertSame('A', $t2->viewData('tanseGrade'));
        $t2->assertSee('Alhamdulillah ananda sudah Sangat Baik', false);
    }

    #[Test]
    public function low_score_gets_the_c_description(): void
    {
        $this->point('violation', 50, '2027-02-01', 'Pelanggaran Berat');

        $response = $this->report(['academic_year' => '2026/2027', 'semester' => 2, 'term' => 3]);

        $this->assertSame('C', $response->viewData('tanseGrade'));
        $response->assertSee('Alhamdulillah ananda sudah Cukup Baik', false);
    }

    #[Test]
    public function a_term_outside_the_semester_falls_back_to_that_semesters_terms(): void
    {
        Carbon::setTestNow('2026-11-15');

        $current = StudentReportController::resolveTanseTerm('2026/2027', 1, 4);
        $this->assertSame(2, $current['term'], 'Triwulan 4 bukan bagian semester 1; pakai triwulan berjalan.');
        $this->assertSame([1, 2], array_keys($current['terms']));

        $past = StudentReportController::resolveTanseTerm('2025/2026', 2);
        $this->assertSame(4, $past['term'], 'Semester yang sudah lewat: triwulan terakhirnya.');
        $this->assertSame('2026-04-01', $past['start']->toDateString());
        $this->assertSame('2026-06-30', $past['end']->toDateString());

        Carbon::setTestNow();
    }

    #[Test]
    public function printed_report_shows_one_merged_description_for_the_triwulan(): void
    {
        $this->point('violation', 12, '2026-08-10', 'Tidak Memakai Atribut');

        $response = $this->actingAs($this->admin)->get(route('digital-reports.print', [
            $this->student, 'academic_year' => '2026/2027', 'semester' => 1, 'term' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Triwulan 1 (Jul - Sep)');
        $response->assertSee('Predikat B');
        $response->assertSee('rowspan="2"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'Alhamdulillah ananda sudah'), 'Deskripsi Tanse hanya satu sel.');
        $response->assertDontSee('Tidak Memakai Atribut (-12 Poin)');
    }

    #[Test]
    public function tanse_thresholds_and_descriptions_follow_report_settings(): void
    {
        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2026/2027', 'semester' => 1,
            'report_main_title' => 'LAPORAN', 'report_school_name' => 'SMA', 'report_city' => 'Sukoharjo',
            'tanse_a_min' => 95, 'tanse_b_min' => 85,
            'tanse_notes' => ['A' => 'Deskripsi A baru', 'B' => '', 'C' => 'Deskripsi C baru'],
        ])->assertRedirect();

        $this->assertSame('B', StudentReportController::tanseGrade(90), '90 kini di bawah batas A (95).');
        $this->assertSame('C', StudentReportController::tanseGrade(84));
        $this->assertSame('Deskripsi A baru', StudentReportController::tanseNote('A'));
        $this->assertSame(StudentReportController::TANSE_NOTES['B'], StudentReportController::tanseNote('B'), 'Kosong = kembali ke bawaan.');
    }

    #[Test]
    public function tanse_b_threshold_must_be_below_a(): void
    {
        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2026/2027', 'semester' => 1,
            'report_main_title' => 'LAPORAN', 'report_school_name' => 'SMA', 'report_city' => 'Sukoharjo',
            'tanse_a_min' => 80, 'tanse_b_min' => 90,
        ])->assertSessionHasErrors('tanse_b_min');
    }
}
