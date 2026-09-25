<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use App\Services\QuranLineTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuranLineTargetServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuranLineTargetService $service;

    private $surahs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuranLineTargetService;

        foreach ([[1, 'Al-Fatihah', 7], [2, 'Al-Baqarah', 286], [3, "Ali 'Imran", 200]] as [$number, $name, $total]) {
            Surah::create([
                'number' => $number,
                'name_ar' => $name,
                'name_latin' => $name,
                'total_ayah' => $total,
                'juz_start' => 1,
                'juz_end' => 1,
            ]);
        }

        $this->surahs = Surah::query()->get()->keyBy('number');
    }

    #[Test]
    public function target_stays_in_the_start_surah_when_lines_fit(): void
    {
        $position = $this->service->targetPosition(1, 1, 3, $this->surahs);

        $this->assertSame(1, $position['surah']->number);
        $this->assertSame(1, $position['ayah_start']);
        // Ayat 1-3 Al-Fatihah = 3 baris mushaf.
        $this->assertSame(3, $position['ayah_end']);
        $this->assertSame(3.0, ReportController::calculateLines(1, 1, $position['ayah_end'], 7));
    }

    #[Test]
    public function target_continues_into_the_next_surah_when_start_surah_runs_out(): void
    {
        $fatihahLines = ReportController::calculateLines(1, 1, 7, 7);

        $position = $this->service->targetPosition(1, 1, $fatihahLines + 5, $this->surahs);

        $this->assertSame(2, $position['surah']->number);
        $this->assertSame(1, $position['ayah_start']);

        // Sisa 5 baris dihitung dari awal Al-Baqarah: ayat akhir adalah yang pertama mencapai 5 baris.
        $this->assertGreaterThanOrEqual(5, ReportController::calculateLines(2, 1, $position['ayah_end'], 286));
        $this->assertLessThan(5, ReportController::calculateLines(2, 1, $position['ayah_end'] - 1, 286));
    }

    #[Test]
    public function start_ayah_in_the_middle_of_a_surah_is_respected(): void
    {
        $position = $this->service->targetPosition(2, 10, 6, $this->surahs);

        $this->assertSame(2, $position['surah']->number);
        $this->assertSame(10, $position['ayah_start']);
        $this->assertGreaterThanOrEqual(6, ReportController::calculateLines(2, 10, $position['ayah_end'], 286));
    }

    #[Test]
    public function no_target_when_target_lines_is_zero_or_surah_is_unknown(): void
    {
        $this->assertNull($this->service->targetPosition(1, 1, 0, $this->surahs));
        $this->assertNull($this->service->targetPosition(50, 1, 10, $this->surahs));
    }

    #[Test]
    public function latest_by_position_returns_null_for_empty_collection(): void
    {
        $this->assertNull($this->service->latestByPosition(collect()));
    }

    #[Test]
    public function latest_by_position_prefers_the_furthest_surah_on_a_same_date_tie(): void
    {
        // Al-Qamar (54) di-input dulu, lalu Ar-Rahman (55) ditambahkan lewat "+Tambah
        // Surat" di sesi/tanggal yang sama -- Ar-Rahman lebih jauh di mushaf dan harus
        // dianggap capaian terakhir, walau bukan entri pertama yang ter-load.
        $alQamar = (object) ['surah' => (object) ['number' => 54], 'ayah_end' => 55, 'submitted_at' => '2026-09-10'];
        $arRahman = (object) ['surah' => (object) ['number' => 55], 'ayah_end' => 4, 'submitted_at' => '2026-09-10'];

        $result = $this->service->latestByPosition(collect([$alQamar, $arRahman]));

        $this->assertSame(55, $result->surah->number);
    }

    #[Test]
    public function latest_by_position_still_prefers_a_later_date_over_a_further_surah(): void
    {
        $olderButFurther = (object) ['surah' => (object) ['number' => 90], 'ayah_end' => 1, 'submitted_at' => '2026-08-01'];
        $newerButCloser = (object) ['surah' => (object) ['number' => 2], 'ayah_end' => 5, 'submitted_at' => '2026-09-10'];

        $result = $this->service->latestByPosition(collect([$olderButFurther, $newerButCloser]));

        $this->assertSame(2, $result->surah->number);
    }

    #[Test]
    public function latest_by_position_breaks_a_same_surah_tie_by_ayah_end(): void
    {
        $partial = (object) ['surah' => (object) ['number' => 2], 'ayah_end' => 10, 'submitted_at' => '2026-09-10'];
        $further = (object) ['surah' => (object) ['number' => 2], 'ayah_end' => 20, 'submitted_at' => '2026-09-10'];

        $result = $this->service->latestByPosition(collect([$partial, $further]));

        $this->assertSame(20, $result->ayah_end);
    }
}
