<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AutoHafalanTargetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target hafalan otomatis (kelas 11 & 12): satu target per murid per bulan dari setoran
 * pertama term + jumlah pertemuan terjadwal x baris level; target buatan guru selalu menang.
 */
class AutoHafalanTargetTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $class12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        Surah::firstOrCreate(
            ['number' => 2],
            ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]
        );

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        // Rabu saja: Juli 5, Agustus 4, September 5 pertemuan (default libur nasional tidak jatuh di Rabu).
        $this->class12 = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F3',
            'level' => 'XII',
            'tahfizh_days' => [3],
        ]);
        $this->student->update(['class_room_id' => $this->class12->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $ayahStart = 1, int $ayahEnd = 3): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => $date,
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => $ayahStart,
            'ayah_end' => $ayahEnd,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);
    }

    private function sync(): void
    {
        app(AutoHafalanTargetService::class)->syncStudent($this->student->fresh(), Carbon::parse('2026-07-01'));
    }

    private function autoTargets()
    {
        return HafalanTarget::withTrashed()
            ->where('student_id', $this->student->id)
            ->whereNotNull('auto_month')
            ->orderBy('auto_month')
            ->get();
    }

    #[Test]
    public function creates_one_cumulative_target_per_month_and_is_idempotent(): void
    {
        $this->setoran('2026-07-08');

        $this->sync();
        $this->sync();

        $targets = $this->autoTargets();
        $this->assertSame(['2026-07', '2026-08', '2026-09'], $targets->pluck('auto_month')->all());

        // Target akhir tiap bulan dihitung kumulatif, jadi tidak mundur.
        $keys = $targets->map(fn ($t) => $t->surah->number * 1000 + $t->ayah)->all();
        $sorted = $keys;
        sort($sorted);
        $this->assertSame($sorted, $keys);
        $this->assertSame(['2026-07-31', '2026-08-31', '2026-09-30'], $targets->map(fn ($t) => $t->target_date->toDateString())->all());
        $this->assertSame('active', $targets->first()->status);
        $this->assertSame($this->teacherProfile->id, $targets->first()->teacher_id);
    }

    #[Test]
    public function month_with_a_guru_target_gets_no_auto_target_and_existing_auto_row_is_removed(): void
    {
        $this->setoran('2026-07-08');
        $this->sync();
        $this->assertCount(3, $this->autoTargets());

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => '2026-08-15',
            'status' => 'active',
        ]);
        $this->sync();

        $this->assertSame(['2026-07', '2026-09'], $this->autoTargets()->pluck('auto_month')->all());
        $this->assertSame(1, HafalanTarget::query()->where('student_id', $this->student->id)->whereNull('auto_month')->count());
    }

    #[Test]
    public function auto_target_deleted_by_guru_is_not_recreated(): void
    {
        $this->setoran('2026-07-08');
        $this->sync();

        $this->autoTargets()->firstWhere('auto_month', '2026-08')->delete();
        $this->sync();

        $this->assertNull(HafalanTarget::query()->where('student_id', $this->student->id)->where('auto_month', '2026-08')->first());
        $this->assertSame(1, HafalanTarget::onlyTrashed()->where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function editing_an_auto_target_turns_it_into_a_guru_target(): void
    {
        $this->setoran('2026-07-08');
        $this->sync();
        $auto = $this->autoTargets()->firstWhere('auto_month', '2026-07');

        $this->actingAs($this->admin)->put(route('hafalan-targets.update', $auto), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah' => 6,
            'target_date' => '2026-07-31',
        ])->assertRedirect();

        $this->assertNull($auto->fresh()->auto_month);

        $this->sync();
        $this->assertSame(6, $auto->fresh()->ayah);
        $this->assertSame(['2026-08', '2026-09'], $this->autoTargets()->pluck('auto_month')->all());
    }

    #[Test]
    public function an_earlier_setoran_shifts_the_start_point_and_recalculates_without_duplicates(): void
    {
        $this->setoran('2026-08-05', 1, 3);
        $this->sync();
        $before = $this->autoTargets()->firstWhere('auto_month', '2026-09');

        // Musyrif baru menginput setoran Juli yang lebih awal: titik awal bergeser ke situ.
        $this->setoran('2026-07-01', 1, 3);
        $this->sync();

        $targets = $this->autoTargets();
        $this->assertCount(3, $targets);
        $this->assertSame($before->id, $targets->firstWhere('auto_month', '2026-09')->id);
    }

    #[Test]
    public function no_auto_target_for_grade_10_ummi_level_or_students_without_setoran(): void
    {
        // Belum ada setoran di term ini.
        $this->sync();
        $this->assertCount(0, $this->autoTargets());

        $this->setoran('2026-07-08');

        $this->student->update(['tahfizh_level' => 'ummi']);
        $this->sync();
        $this->assertCount(0, $this->autoTargets());

        $this->student->update(['tahfizh_level' => 'reguler']);
        $this->class12->update(['name' => 'Kelas X E1', 'level' => 'X']);
        $this->sync();
        $this->assertCount(0, $this->autoTargets());
    }

    #[Test]
    public function saving_a_setoran_through_the_app_creates_the_auto_targets(): void
    {
        $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => '2026-07-08',
        ])->assertRedirect();

        $this->assertSame(
            ['2026-07', '2026-08', '2026-09'],
            $this->autoTargets()->pluck('auto_month')->all()
        );
    }

    #[Test]
    public function periodic_report_marks_tuntas_when_capaian_reaches_the_target_position(): void
    {
        $this->setoran('2026-09-09', 1, 7); // Al-Fatihah sampai ayat 7 -> hanya 7 baris

        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => '2026-09-15',
            'status' => 'active',
        ]);

        $query = ['class_room_id' => $this->class12->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026];
        $report = fn () => collect($this->actingAs($this->admin)->get(route('reports.periodic', $query))->viewData('studentReports'))
            ->first(fn ($r) => $r['student']->id === $this->student->id);

        // Baris belum memenuhi target (5 pertemuan x 5 = 25), tapi ayat 7 >= target ayat 5.
        $row = $report();
        $this->assertLessThan($row['target_baris'], $row['capaian_baris']);
        $this->assertTrue($row['is_tuntas']);

        // Target di surah yang lebih jauh (Al-Baqarah) belum tercapai.
        $baqarah = Surah::where('number', 2)->first();
        $target->update(['surah_id' => $baqarah->id, 'ayah' => 10]);
        $this->assertFalse($report()['is_tuntas']);
    }
}
