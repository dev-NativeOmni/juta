<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AutoHafalanTargetService;
use App\Support\HafalanOrder;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target otomatis mengikuti urutan hafalan sekolah dan halaman Target Triwulan.
 */
class TargetTriwulanTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        // Surah Juz 28-30 dengan jumlah ayat dari batas juz.
        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end'],
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F3', 'level' => 'XII', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $surahNumber, int $from, int $to): void
    {
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $record->surahs()->create([
            'surah_id' => Surah::where('number', $surahNumber)->value('id'),
            'ayah_start' => $from, 'ayah_end' => $to, 'submission_type' => 'new', 'status' => 'passed',
        ]);
    }

    /** Semua surah Juz 30 selain An-Naba sudah disetor sebelum triwulan. */
    private function finishJuz30ExceptAnNaba(): void
    {
        foreach (range(79, 114) as $number) {
            $this->setoran('2026-06-10', $number, 1, (int) Surah::where('number', $number)->value('total_ayah'));
        }
    }

    #[Test]
    public function monthly_targets_move_from_juz_30_into_the_start_of_juz_29_not_al_fatihah(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5); // setoran pertama triwulan

        app(AutoHafalanTargetService::class)->syncStudent($this->student->fresh(), Carbon::parse('2026-07-01'));

        $targets = HafalanTarget::where('student_id', $this->student->id)->whereNotNull('auto_month')->orderBy('auto_month')->with('surah')->get();
        $this->assertCount(3, $targets);
        $last = $targets->last();
        $this->assertTrue(
            $last->surah->number >= 67 && $last->surah->number <= 78,
            "Target akhir triwulan harus di An-Naba/Juz 29, bukan surah {$last->surah->number}."
        );
    }

    #[Test]
    public function term_plan_matches_the_last_monthly_checkpoint_and_tracks_progress(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);
        $this->setoran('2026-07-08', 78, 6, 40);
        $this->setoran('2026-07-15', 67, 1, 10); // lanjut ke awal Juz 29

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-15'));

        $this->assertSame(78, $plan['start']['surah']->number);
        $this->assertSame(14, $plan['term_meetings'], 'Rabu Jul-Sep 2026: 5 + 4 + 5 pertemuan.');
        $this->assertSame(70, $plan['target_lines'], '14 pertemuan x 5 baris.');
        $this->assertSame(end($plan['months'])['position']['surah']->number, $plan['target']['surah']->number);
        $this->assertSame(67, $plan['capaian']['surah']->number, 'Capaian terjauh = Al-Mulk, bukan An-Naba.');
        $this->assertGreaterThan(0, $plan['progress']);
    }

    #[Test]
    public function term_page_lists_students_of_grade_11_12_only(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]));

        $response->assertOk();
        $response->assertSee('Target Triwulan');
        $response->assertSee($this->student->name);
        $this->assertSame(1, $response->viewData('summary')['students']);
        $this->assertFalse($response->viewData('classRooms')->contains(fn ($c) => $c->isGradeTen()));
    }

    #[Test]
    public function staff_can_switch_a_students_direction_from_the_term_page(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'front_29', 'period' => '2026-07-01'])
            ->assertRedirect();

        $this->assertSame('front_29', $this->student->fresh()->hafalan_direction);
    }

    #[Test]
    public function staff_cannot_switch_direction_for_students_they_cannot_see(): void
    {
        $this->student->update(['teacher_id' => null]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'front_29'])
            ->assertForbidden();
        $this->assertSame('backward', $this->student->fresh()->hafalan_direction);
    }

    #[Test]
    public function a_teachers_manual_target_becomes_the_term_target_everywhere(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);
        app(AutoHafalanTargetService::class)->syncStudent($this->student->fresh(), Carbon::parse('2026-07-01'));

        // Guru mengganti target September.
        HafalanTarget::where('student_id', $this->student->id)->where('auto_month', '2026-09')->forceDelete();
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => Surah::where('number', 67)->value('id'), 'ayah' => 15,
            'target_date' => '2026-09-30', 'status' => 'active',
        ]);

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-01'));
        $this->assertSame('manual', $plan['target_source']);
        $this->assertSame(67, $plan['target']['surah']->number);
        $this->assertSame(15, $plan['target']['ayah_end']);
        $this->assertSame('manual', $plan['months']['2026-09']['source']);

        // Laporan Triwulan memakai target yang sama.
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', ['class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1']));
        $termRecord = $response->viewData('halaqahData')[0]['term_records'][0];
        $this->assertSame('Surah 67', $termRecord['target_surah']);
    }

    #[Test]
    public function target_rules_are_editable_and_drive_the_calculation(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);

        $this->actingAs($this->admin)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 2, 'reguler' => 4, 'akselerasi' => 8],
            'mandatory_until' => 30, 'latest_switch' => 28,
        ])->assertRedirect(route('settings.hafalan-targets'));

        $this->assertSame(4, TargetRules::linesForLevel('reguler'));
        $this->assertSame([30, 29, 28], TargetRules::switchOptions());
        $this->assertArrayHasKey('front_30', HafalanOrder::directionOptions());
        $this->assertSame([30, 1, 2], array_slice(HafalanOrder::juzSequence('front_30'), 0, 3), 'Pindah setelah Juz 30 langsung ke Juz 1.');

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-01'));
        $this->assertSame(14 * 4, $plan['target_lines'], '14 pertemuan x 4 baris (pengaturan baru).');

        $this->actingAs($this->teacherUser)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 1, 'reguler' => 1, 'akselerasi' => 1], 'mandatory_until' => 29, 'latest_switch' => 27,
        ])->assertForbidden();
        $this->actingAs($this->admin)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 3, 'reguler' => 5, 'akselerasi' => 7], 'mandatory_until' => 27, 'latest_switch' => 29,
        ])->assertSessionHasErrors('latest_switch');
    }
}
