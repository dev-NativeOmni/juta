<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Murid yang menghafal satu juz dari akhir (mis. Juz 27: Al-Hadid mundur ke Adz-Dzariyat):
 * urutan terdeteksi dari setoran, bisa dikoreksi guru, dan tuntas dinilai dari cakupan ayat.
 */
class JuzOrderAndCoverageTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        foreach ([26, 27, 28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                $current = Surah::where('number', $range['surah'])->value('total_ayah');
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}",
                    'total_ayah' => max((int) $current, $range['end']),
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F1', 'level' => 'XII', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $surah, int $from, int $to, string $status = 'passed'): void
    {
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $record->surahs()->create([
            'surah_id' => Surah::where('number', $surah)->value('id'),
            'ayah_start' => $from, 'ayah_end' => $to, 'submission_type' => 'new', 'status' => $status,
        ]);
    }

    private function plan(): array
    {
        return app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-01'));
    }

    #[Test]
    public function memorising_juz_27_from_the_end_is_detected_and_the_target_follows_it(): void
    {
        $this->setoran('2026-07-01', 57, 1, 29); // Al-Hadid
        $this->setoran('2026-07-08', 56, 1, 20); // lalu Al-Waqi'ah -> mundur

        $plan = $this->plan();

        $this->assertSame(HafalanOrder::DESC, $plan['juz_orders'][27]);
        $this->assertSame('auto', ($plan['juz_order_source'])(27));
        $this->assertContains($plan['target']['surah']->number, [55, 56], 'Target mundur ke Al-Waqiah/Ar-Rahman, bukan Adz-Dzariyat.');
    }

    #[Test]
    public function teacher_can_override_the_detected_order(): void
    {
        $this->setoran('2026-07-01', 57, 1, 29);
        $this->setoran('2026-07-08', 56, 1, 20);

        $this->actingAs($this->teacherUser)->patch(route('hafalan-targets.juz-order', $this->student), [
            'juz' => 27, 'order' => 'asc', 'period' => '2026-07-01',
        ])->assertRedirect();

        $this->assertSame(['27' => 'asc'], $this->student->fresh()->juz_orders);
        $plan = $this->plan();
        $this->assertSame(HafalanOrder::ASC, $plan['juz_orders'][27]);
        $this->assertSame('manual', ($plan['juz_order_source'])(27));

        // Kembali ke otomatis.
        $this->actingAs($this->teacherUser)->patch(route('hafalan-targets.juz-order', $this->student), ['juz' => 27, 'order' => 'auto']);
        $this->assertNull($this->student->fresh()->juz_orders);
    }

    #[Test]
    public function tuntas_requires_every_ayah_up_to_the_target_in_any_order(): void
    {
        $this->setoran('2026-07-01', 67, 1, 5);   // titik awal triwulan: Al-Mulk
        $this->setoran('2026-07-08', 67, 11, 20); // lompat
        $service = app(HafalanProgressService::class);
        $evaluate = fn () => $service->evaluate($this->student->fresh(), 67, 20, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'), Carbon::parse('2026-09-30'));

        $gap = $evaluate();
        $this->assertFalse($gap['reached'], 'Ayat 6-10 belum disetor.');
        $this->assertGreaterThan(0, $gap['progress']);
        $this->assertLessThan(100, $gap['progress']);

        $this->setoran('2026-07-15', 67, 6, 10); // ditambal belakangan
        $this->assertTrue($evaluate()['reached']);
        $this->assertSame(100, $evaluate()['progress']);
    }

    #[Test]
    public function setoran_that_must_be_repeated_does_not_count_as_covered(): void
    {
        $this->setoran('2026-07-01', 67, 1, 10, 'repeat'); // harus diulang = belum lulus
        $this->setoran('2026-07-02', 67, 1, 5);

        $result = app(HafalanProgressService::class)->evaluate($this->student->fresh(), 67, 10, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'), Carbon::parse('2026-09-30'));

        $this->assertFalse($result['reached']);
    }

    #[Test]
    public function juz_order_page_lists_every_juz_with_coverage_and_detected_order(): void
    {
        $this->setoran('2026-07-01', 57, 1, 29);
        $this->setoran('2026-07-08', 56, 1, 20);

        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-targets.juz-orders', $this->student));

        $response->assertOk();
        $rows = $response->viewData('juzRows')->keyBy('juz');
        $this->assertCount(30, $rows);
        $this->assertSame('desc', $rows[27]['order']);
        $this->assertSame('auto', $rows[27]['source']);
        $this->assertGreaterThan(0, $rows[27]['covered_percent']);
        $this->assertSame(0, $rows[1]['covered_percent']);
        $response->assertSee('otomatis dari setoran');
    }

    #[Test]
    public function juz_order_page_is_limited_to_visible_students(): void
    {
        $this->student->update(['teacher_id' => null]);

        $this->actingAs($this->teacherUser)->get(route('hafalan-targets.juz-orders', $this->student))->assertForbidden();
    }
}
