<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use App\Services\QuranLineTargetService;
use App\Support\HafalanOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Urutan hafalan sekolah: Juz 30 (fleksibel) -> Juz 29 -> Juz 28 ..., tiap juz selain
 * 30 dimulai dari awal juz. Target & capaian mengikuti urutan ini.
 */
class HafalanOrderTargetTest extends TestCase
{
    use RefreshDatabase;

    private QuranLineTargetService $service;

    private Collection $surahs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuranLineTargetService;

        // 114 surah; jumlah ayat = ayat terakhir di batas juz.
        $totals = [];
        foreach (HafalanOrder::JUZ_RANGES as $ranges) {
            foreach ($ranges as $range) {
                $totals[$range['surah']] = max($totals[$range['surah']] ?? 0, $range['end']);
            }
        }
        foreach ($totals as $number => $total) {
            Surah::create(['number' => $number, 'name_ar' => "S{$number}", 'name_latin' => "Surah {$number}", 'total_ayah' => $total]);
        }
        $this->surahs = Surah::query()->get()->keyBy('number');
    }

    private function lines(int $surah, int $from, int $to): float
    {
        return ReportController::calculateLines($surah, $from, $to, (int) $this->surahs[$surah]->total_ayah);
    }

    private function juzLines(int $juz): float
    {
        return collect(HafalanOrder::JUZ_RANGES[$juz])->sum(fn ($r) => $this->lines($r['surah'], $r['start'], $r['end']));
    }

    /** Cakupan (AyahCoverage): semua surah Juz 30 sudah disetor kecuali yang disebut. */
    private function juz30AllDoneExcept(array $pending): array
    {
        return collect(range(78, 114))->reject(fn ($s) => in_array($s, $pending, true))
            ->mapWithKeys(fn ($s) => [$s => [[1, (int) $this->surahs[$s]->total_ayah]]])->all();
    }

    #[Test]
    public function after_finishing_juz_30_the_target_continues_from_the_start_of_juz_29(): void
    {
        // Tinggal An-Naba; sisanya sudah selesai. 5 baris setelah An-Naba -> Al-Mulk dari ayat 1.
        $position = $this->service->targetPosition(78, 1, $this->lines(78, 1, 40) + 5, $this->surahs, $this->juz30AllDoneExcept([78]));

        $this->assertSame(67, $position['surah']->number);
        $this->assertSame(1, $position['ayah_start']);
    }

    #[Test]
    public function after_finishing_juz_29_the_target_continues_from_the_start_of_juz_28(): void
    {
        $position = $this->service->targetPosition(67, 1, $this->juzLines(29) + 3, $this->surahs);

        $this->assertSame(58, $position['surah']->number, 'Juz 28 dimulai dari Al-Mujadilah.');
        $this->assertSame(1, $position['ayah_start']);
    }

    #[Test]
    public function a_juz_that_starts_mid_surah_begins_at_that_ayah(): void
    {
        // Juz 27 dimulai dari Adz-Dzariyat ayat 31.
        $position = $this->service->targetPosition(58, 1, $this->juzLines(28) + 2, $this->surahs);

        $this->assertSame(51, $position['surah']->number);
        $this->assertSame(31, $position['ayah_start']);
    }

    #[Test]
    public function juz_30_counts_only_surahs_not_yet_memorised_before_moving_on(): void
    {
        // Mulai di Al-Qari'ah (101); yang belum: 101, 99, 90. Setelah itu langsung Juz 29.
        $done = $this->juz30AllDoneExcept([101, 99, 90]);
        $afterHundredOne = $this->lines(101, 1, 11);

        $next = $this->service->targetPosition(101, 1, $afterHundredOne + 1, $this->surahs, $done);
        $this->assertSame(99, $next['surah']->number, 'Surah berikutnya = surah Juz 30 yang belum disetor.');

        $all = $afterHundredOne + $this->lines(99, 1, 8) + $this->lines(90, 1, 20);
        $this->assertSame(67, $this->service->targetPosition(101, 1, $all + 1, $this->surahs, $done)['surah']->number);
    }

    #[Test]
    public function after_juz_27_backward_students_continue_to_juz_26(): void
    {
        $position = $this->service->targetPosition(51, 31, $this->juzLines(27) + 2, $this->surahs, [], HafalanOrder::BACKWARD);

        $this->assertSame(46, $position['surah']->number, 'Juz 26 dimulai dari Al-Ahqaf.');
        $this->assertSame(1, $position['ayah_start']);
    }

    #[Test]
    public function after_juz_27_forward_students_move_to_juz_1_then_juz_2(): void
    {
        $toJuz1 = $this->service->targetPosition(51, 31, $this->juzLines(27) + 2, $this->surahs, [], HafalanOrder::FORWARD);
        $this->assertSame(1, $toJuz1['surah']->number, 'Pindah ke depan: Al-Fatihah.');
        $this->assertSame(1, $toJuz1['ayah_start']);

        $toJuz2 = $this->service->targetPosition(1, 1, $this->juzLines(1) + 2, $this->surahs, [], HafalanOrder::FORWARD);
        $this->assertSame(2, $toJuz2['surah']->number);
        $this->assertSame(142, $toJuz2['ayah_start'], 'Setelah Juz 1 lanjut Juz 2 dari Al-Baqarah 142.');
    }

    #[Test]
    public function students_can_move_to_juz_1_right_after_juz_29_or_28(): void
    {
        $after29 = $this->service->targetPosition(67, 1, $this->juzLines(29) + 2, $this->surahs, [], 'front_29');
        $this->assertSame(1, $after29['surah']->number, 'Pindah setelah Juz 29: lanjut Al-Fatihah, bukan Juz 28.');

        $after28 = $this->service->targetPosition(58, 1, $this->juzLines(28) + 2, $this->surahs, [], 'front_28');
        $this->assertSame(1, $after28['surah']->number);

        // front_29: Juz 28 & 27 tidak dilewati selamanya -- ada di ujung urutan setelah Juz 26.
        $this->assertSame([30, 29, 1], array_slice(HafalanOrder::juzSequence('front_29'), 0, 3));
        $this->assertSame([27, 28], array_slice(HafalanOrder::juzSequence('front_29'), -2));
        $this->assertTrue($this->service->hasReached(2, 5, 77, 50, 'front_29'), 'Juz 1 melewati akhir Juz 29.');
        $this->assertFalse($this->service->hasReached(2, 5, 58, 1, 'front_29'), 'Juz 28 datang setelah Juz 1..27, jadi Juz 1 belum mencapainya.');
    }

    #[Test]
    public function legacy_and_unknown_directions_are_normalised(): void
    {
        $this->assertSame('front_27', HafalanOrder::normalizeDirection('forward'));
        $this->assertSame('backward', HafalanOrder::normalizeDirection('front_31'), 'Titik pindah di luar Juz 2-30 tidak sah.');
        $this->assertSame('front_30', HafalanOrder::normalizeDirection('front_30'), 'Boleh bila pengaturan juz wajib = Juz 30.');
        $this->assertSame('backward', HafalanOrder::normalizeDirection(null));
        $this->assertNull(HafalanOrder::switchJuz('backward'));
        $this->assertSame(28, HafalanOrder::switchJuz('front_28'));
    }

    #[Test]
    public function juz_30_to_27_always_come_first_in_both_directions(): void
    {
        $this->assertSame([30, 29, 28, 27, 26], array_slice(HafalanOrder::juzSequence(HafalanOrder::BACKWARD), 0, 5));
        $this->assertSame([30, 29, 28, 27, 1, 2], array_slice(HafalanOrder::juzSequence(HafalanOrder::FORWARD), 0, 6));

        // Kedua arah: Juz 1 datang setelah Juz 27; Juz 28 belum melewati Juz 27.
        $this->assertTrue($this->service->hasReached(2, 5, 57, 29, HafalanOrder::FORWARD));
        $this->assertFalse($this->service->hasReached(58, 1, 57, 1, HafalanOrder::FORWARD));

        // Beda arah: ke depan Juz 26 paling akhir (melewati Juz 1); ke belakang Juz 26 sebelum Juz 1.
        $this->assertTrue($this->service->hasReached(46, 5, 2, 5, HafalanOrder::FORWARD));
        $this->assertFalse($this->service->hasReached(46, 5, 2, 5, HafalanOrder::BACKWARD));
    }

    #[Test]
    public function reaching_is_judged_by_memorisation_order_not_surah_number(): void
    {
        $this->assertTrue($this->service->hasReached(67, 1, 78, 40), 'Al-Mulk (Juz 29) sudah melewati An-Naba.');
        $this->assertFalse($this->service->hasReached(78, 40, 67, 1));
        $this->assertTrue($this->service->hasReached(58, 1, 77, 50), 'Juz 28 melewati Juz 29.');
        $this->assertTrue($this->service->hasReached(67, 10, 67, 5));
    }

    #[Test]
    public function pieces_until_a_target_follow_the_same_path(): void
    {
        $this->assertSame([[67, 1, 10]], $this->service->piecesUntil(67, 1, 67, 10, $this->surahs));

        $pieces = $this->service->piecesUntil(67, 1, 58, 3, $this->surahs);
        $this->assertSame($this->juzLines(29) + $this->lines(58, 1, 3), $this->service->piecesLines($pieces, $this->surahs));

        // Target di belakang titik awal tidak ada di jalur ke depan.
        $this->assertNull($this->service->piecesUntil(58, 1, 67, 5, $this->surahs));
    }

    #[Test]
    public function latest_record_on_the_same_day_is_the_furthest_in_memorisation_order(): void
    {
        $record = fn (int $surah, int $ayah) => (object) ['submitted_at' => '2026-10-01', 'surah' => $this->surahs[$surah], 'ayah_end' => $ayah];

        $latest = $this->service->latestByPosition(collect([$record(78, 40), $record(67, 5)]));

        $this->assertSame(67, $latest->surah->number);
    }
}
