<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Download Laporan Triwulan per kelas ke file .xlsx nyata (bukan sekadar CSV
 * berlabel xlsx) dengan satu sheet per tab yang tampil di layar: Term-Indeks,
 * Presensi, Jurnal, Setoran -- isinya harus sama persis dengan data yang dipakai
 * untuk merender halaman (lihat QuarterlyReportController::buildReportData()).
 */
class QuarterlyReportExportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function downloadAndLoad(array $query): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', $query));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tmpPath = tempnam(sys_get_temp_dir(), 'qre').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    /**
     * Sama seperti downloadAndLoad(), tapi baca ulang dengan chart diaktifkan --
     * reader PhpSpreadsheet defaultnya melewati chart demi performa.
     */
    private function downloadAndLoadWithCharts(array $query): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', $query));
        $response->assertStatus(200);

        $tmpPath = tempnam(sys_get_temp_dir(), 'qrec').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());

        $reader = new Xlsx;
        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    #[Test]
    public function export_produces_a_real_xlsx_with_one_sheet_per_tab_and_only_the_selected_class(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F4 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $otherClass = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas Lain', 'level' => 'XII']);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-901',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'status' => 'hadir',
        ]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ])->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);
        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => 'violation',
            'points' => 5,
            'title' => 'Terlambat',
            'date' => '2026-07-06',
            'logged_by' => $this->teacherUser->id,
        ]);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $sheetTitles = array_map(fn ($s) => $s->getTitle(), $spreadsheet->getAllSheets());
        $this->assertSame(['Term-Indeks', 'Presensi', 'Jurnal', 'Setoran', 'Grafik Akhir Bulan'], $sheetTitles);

        // Term-Indeks: dikelompokkan per tingkat kelas ("KELAS XII"), lalu per kelas/halaqoh
        // ("Kelas: ... | Musyrif: ..."), diikuti baris judul kolom, lalu satu baris per murid.
        $termSheet = $spreadsheet->getSheetByName('Term-Indeks');
        $rows = $termSheet->toArray();
        $this->assertSame('KELAS XII', $rows[0][0]);
        $this->assertSame('Kelas: Kelas XII F4 Export  |  Musyrif: Guru Test', $rows[1][0]);
        $this->assertSame(
            ['No', 'Nama Murid', 'Level', 'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat', 'Capaian Baris', 'Target Baris', 'Ketercapaian', 'Alpa', 'Izin', 'Sakit', 'Pelanggaran'],
            $rows[2]
        );
        $studentRow = collect($rows)->firstWhere(1, $this->student->name);
        $this->assertNotNull($studentRow);
        // Pelanggaran murid muncul di kolom terakhir (Term-Indeks).
        $this->assertSame('1', (string) $studentRow[13]);

        $allTermCells = $this->flatten($rows);
        $this->assertNotContains($otherStudent->name, $allTermCells);

        $presensiSheet = $spreadsheet->getSheetByName('Presensi');
        $presensiRows = $presensiSheet->toArray();
        $this->assertGreaterThan(1, count($presensiRows));

        $setoranSheet = $spreadsheet->getSheetByName('Setoran');
        $setoranCells = $this->flatten($setoranSheet->toArray());
        $this->assertTrue(collect($setoranCells)->contains(fn ($v) => str_contains((string) $v, 'Al-Fatihah')));

        $grafikSheet = $spreadsheet->getSheetByName('Grafik Akhir Bulan');
        $grafikCells = $this->flatten($grafikSheet->toArray());
        $this->assertContains($this->student->name, $grafikCells);
    }

    private function flatten(array $rows): array
    {
        return collect($rows)->flatten()->all();
    }

    #[Test]
    public function pekan_column_headers_show_the_real_meeting_day_and_date_not_just_a_generic_label(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Tanggal Pertemuan',
            'level' => 'XII',
            'tahfizh_days' => [4], // kelas cuma tatap muka tiap Kamis.
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $setoranHeader = $this->flatten($spreadsheet->getSheetByName('Setoran')->toArray());
        $pekanCell = collect($setoranHeader)->first(fn ($v) => is_string($v) && str_starts_with($v, 'PEKAN 1 ('));
        $this->assertNotNull($pekanCell, 'Header PEKAN 1 tidak ditemukan di sheet Setoran.');
        $this->assertMatchesRegularExpression('/Kamis, \d{1,2} Jul/', $pekanCell);

        $presensiHeader = $this->flatten($spreadsheet->getSheetByName('Presensi')->toArray());
        $presensiPekanCell = collect($presensiHeader)->first(fn ($v) => is_string($v) && str_starts_with($v, 'PEKAN 1 ('));
        $this->assertNotNull($presensiPekanCell, 'Header PEKAN 1 tidak ditemukan di sheet Presensi.');
        $this->assertMatchesRegularExpression('/Kamis, \d{1,2} Jul/', $presensiPekanCell);
    }

    #[Test]
    public function export_can_be_narrowed_down_to_a_single_halaqoh(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F5 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
            'name' => 'Murid Halaqoh Satu',
        ]);

        $secondTeacherRole = Role::where('name', 'teacher')->firstOrFail();
        $secondTeacherUser = User::factory()->create(['role_id' => $secondTeacherRole->id, 'name' => 'Ust. Halaqoh Dua', 'status' => 'active']);
        $secondTeacherProfile = TeacherProfile::create(['user_id' => $secondTeacherUser->id, 'employee_number' => 'TEST-GURU-002']);
        $secondStudent = Student::create([
            'class_room_id' => $classRoom->id,
            'teacher_id' => $secondTeacherProfile->id,
            'name' => 'Murid Halaqoh Dua',
            'student_number' => 'TEST-SNT-902',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $query = [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ];

        // Pastikan dua halaqoh benar-benar terbentuk sebelum diuji filternya.
        $fullSpreadsheet = $this->downloadAndLoad($query);
        $fullNames = $this->flatten($fullSpreadsheet->getSheetByName('Term-Indeks')->toArray());
        $this->assertContains('Murid Halaqoh Satu', $fullNames);
        $this->assertContains('Murid Halaqoh Dua', $fullNames);

        $filtered = $this->downloadAndLoad($query + ['musyrif' => $this->teacherUser->name]);
        $filteredNames = $this->flatten($filtered->getSheetByName('Term-Indeks')->toArray());
        $this->assertContains('Murid Halaqoh Satu', $filteredNames);
        $this->assertNotContains('Murid Halaqoh Dua', $filteredNames);
    }

    #[Test]
    public function only_admin_super_admin_and_teacher_can_download_the_export(): void
    {
        // Guru boleh, tapi datanya dibatasi ke murid yang dia ampu (QuarterlyReportTeacherPreviewTest).
        $this->get(route('reports.quarterly.export'))->assertRedirect(route('login'));
        $this->actingAs($this->parentUser)->get(route('reports.quarterly.export'))->assertStatus(403);
        $this->actingAs($this->studentUser)->get(route('reports.quarterly.export'))->assertStatus(403);
    }

    #[Test]
    public function grafik_akhir_bulan_includes_a_pie_and_a_bar_line_chart_per_month_with_the_tuntas_percentage(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Donut Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $spreadsheet = $this->downloadAndLoadWithCharts([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $sheet = $spreadsheet->getSheetByName('Grafik Akhir Bulan');
        // Dua chart per bulan (Juli, Agustus, September) di dalam term ini: batang+garis capaian, dan pie ketuntasan.
        $this->assertSame(6, $sheet->getChartCount());

        $titles = collect($sheet->getChartCollection())->map(fn ($c) => $c->getTitle()->getCaptionText());
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Ketuntasan')));
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Grafik Capaian')));

        // Chart pie ketuntasan pakai DataSeries bertipe pieChart sungguhan, dengan warna
        // per-irisan teal/rose (sama seperti donat "Ketuntasan" di Rapor Periodik).
        $pieChart = collect($sheet->getChartCollection())->first(fn ($c) => str_contains($c->getTitle()->getCaptionText(), 'Ketuntasan'));
        $pieSeries = $pieChart->getPlotArea()->getPlotGroup()[0];
        $this->assertSame('pieChart', $pieSeries->getPlotType());
        $this->assertSame(['0D9488', 'F43F5E'], $pieSeries->getPlotValues()[0]->getFillColor());

        // Chart batang+garis benar-benar dua tipe series berbeda (kombinasi), bukan cuma satu,
        // dengan warna biru langit untuk batang (sama seperti chart "Capaian" di Rapor Periodik).
        $comboChart = collect($sheet->getChartCollection())->first(fn ($c) => str_contains($c->getTitle()->getCaptionText(), 'Grafik Capaian'));
        $comboSeries = collect($comboChart->getPlotArea()->getPlotGroup());
        $plotTypes = $comboSeries->map(fn ($s) => $s->getPlotType());
        $this->assertContains('barChart', $plotTypes);
        $this->assertContains('lineChart', $plotTypes);
        $barSeries = $comboSeries->first(fn ($s) => $s->getPlotType() === 'barChart');
        $this->assertSame('0EA5E9', $barSeries->getPlotValues()[0]->getFillColor());

        // Data mentah donat (label berisi persentase) ada di kolom G/H, dibaca langsung oleh chart.
        $cells = $this->flatten($sheet->toArray());
        $this->assertTrue(collect($cells)->contains(fn ($v) => is_string($v) && str_contains($v, 'TUNTAS (') && str_contains($v, '%)')));
    }
}
