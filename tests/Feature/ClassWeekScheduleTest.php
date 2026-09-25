<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ClassWeekSchedule;
use App\Models\Program;
use App\Services\AcademicCalendarService;
use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Jadwal kelas per pekan: jadwal khusus, kunci pekan, dan pekan lampau yang
 * otomatis terkunci (tidak ikut berubah saat jadwal default diganti).
 */
class ClassWeekScheduleTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-10-21 10:00'); // Rabu

        $program = Program::create(['name' => 'Program Pekanan', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Pekanan', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->classRoom->forceFill(['created_at' => '2026-07-01'])->saveQuietly();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function calendar(): SchoolCalendar
    {
        return app(SchoolCalendar::class);
    }

    private function effective(string $date): bool
    {
        return $this->calendar()->isTahfizhEffectiveDay($this->classRoom->fresh(), Carbon::parse($date));
    }

    #[Test]
    public function special_schedule_applies_only_to_that_week(): void
    {
        // Pekan 26 Okt: hanya Senin & Rabu.
        $this->actingAs($this->admin)->post(route('class-schedules.week.update'), [
            'week' => '2026-10-26',
            'schedules' => [$this->classRoom->id => [1, 3]],
        ])->assertRedirect();

        $this->assertTrue($this->effective('2026-10-26'));
        $this->assertFalse($this->effective('2026-10-27'));
        $this->assertTrue($this->effective('2026-10-28'));
        $this->assertTrue($this->effective('2026-11-03'), 'Pekan berikutnya kembali ke jadwal default.');
        $this->assertTrue(ClassWeekSchedule::first()->is_custom);
    }

    #[Test]
    public function week_with_no_days_means_no_meetings_and_affects_tatap_muka(): void
    {
        $this->calendar()->saveWeek($this->classRoom, Carbon::parse('2026-10-26'), []);

        $this->assertSame(0, app(AcademicCalendarService::class)->scheduledMeetings(
            $this->classRoom->fresh(), Carbon::parse('2026-10-26'), Carbon::parse('2026-10-30')
        ));
    }

    #[Test]
    public function past_weeks_are_locked_automatically(): void
    {
        $state = $this->calendar()->weekState($this->classRoom, Carbon::parse('2026-10-12'));
        $this->assertTrue($state['locked']);
        $this->assertTrue($state['auto_locked']);

        $this->actingAs($this->admin)->post(route('class-schedules.week.update'), [
            'week' => '2026-10-12', 'schedules' => [$this->classRoom->id => [1]],
        ]);
        $this->assertTrue($this->effective('2026-10-13'), 'Pekan lampau yang terkunci tidak boleh berubah.');

        // Pekan berjalan belum terkunci.
        $this->assertFalse($this->calendar()->weekState($this->classRoom, Carbon::parse('2026-10-19'))['locked']);
    }

    #[Test]
    public function changing_the_default_schedule_does_not_rewrite_past_weeks(): void
    {
        $this->actingAs($this->admin)->post(route('class-schedules.update'), [
            'schedules' => [1 => [$this->classRoom->id => 1], 3 => [$this->classRoom->id => 1]], // jadi Senin & Rabu
        ])->assertRedirect();

        $this->assertSame([1, 3], $this->classRoom->fresh()->tahfizh_days);
        $this->assertTrue($this->effective('2026-10-13'), 'Selasa di pekan lampau tetap pertemuan (jadwal lama).');
        $this->assertTrue($this->effective('2026-07-07'), 'Awal tahun ajaran juga tetap memakai jadwal lama.');
        $this->assertFalse($this->effective('2026-10-20'), 'Pekan berjalan memakai jadwal baru.');
        $this->assertFalse($this->effective('2026-10-27'), 'Pekan depan memakai jadwal baru.');
    }

    #[Test]
    public function admin_can_unlock_a_past_week_edit_it_and_lock_it_again(): void
    {
        $lock = fn (string $action) => $this->actingAs($this->admin)->post(route('class-schedules.week.lock'), [
            'week' => '2026-10-12', 'action' => $action, 'class_room_id' => $this->classRoom->id,
        ])->assertRedirect();

        $lock('unlock');
        $this->assertFalse($this->calendar()->weekState($this->classRoom, Carbon::parse('2026-10-12'))['locked']);

        $this->actingAs($this->admin)->post(route('class-schedules.week.update'), [
            'week' => '2026-10-12', 'schedules' => [$this->classRoom->id => [1, 2]],
        ]);
        $this->assertFalse($this->effective('2026-10-14'));

        $lock('lock');
        $this->assertTrue($this->calendar()->weekState($this->classRoom, Carbon::parse('2026-10-12'))['locked']);
    }

    #[Test]
    public function manually_locked_future_week_rejects_changes(): void
    {
        $this->calendar()->lockWeek($this->classRoom, Carbon::parse('2026-11-02'));

        $this->assertFalse($this->calendar()->saveWeek($this->classRoom->fresh(), Carbon::parse('2026-11-02'), [1]));
        $this->assertTrue($this->effective('2026-11-04'));
    }

    #[Test]
    public function only_admins_can_manage_weekly_schedules(): void
    {
        $this->actingAs($this->teacherUser)->post(route('class-schedules.week.update'), ['week' => '2026-10-26'])->assertForbidden();
        $this->actingAs($this->teacherUser)->post(route('class-schedules.week.lock'), ['week' => '2026-10-26', 'action' => 'lock'])->assertForbidden();
        $this->actingAs($this->admin)->get(route('class-schedules.index', ['tab' => 'weekly', 'week' => '2026-10-26']))
            ->assertOk()
            ->assertViewHas('weekStates');
    }
}
