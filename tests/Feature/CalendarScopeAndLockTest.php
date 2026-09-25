<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kalender dengan cakupan Tahfizh/Adab, akses Koordinator Adab, dan kunci per bulan.
 */
class CalendarScopeAndLockTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private User $adabCoordinator;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        // Koordinator Adab = role supervisor ("Koordinator Keagamaan").
        $role = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Koordinator Keagamaan']);
        $this->adabCoordinator = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $program = Program::create(['name' => 'Program Kalender', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Kalender', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
    }

    private function calendar(): SchoolCalendar
    {
        return app(SchoolCalendar::class);
    }

    private function save(User $user, array $payload)
    {
        return $this->actingAs($user)->post(route('academic-calendar.update'), ['year' => 2026, 'month' => 10] + $payload);
    }

    #[Test]
    public function admin_can_set_tahfizh_only_holiday_so_adab_keeps_running(): void
    {
        // ASTS: Selasa 13 Okt 2026 Tahfizh libur, Adab tetap.
        $this->save($this->admin, ['days' => ['2026-10-13' => ['tahfizh' => 1]]])->assertRedirect();

        $date = Carbon::parse('2026-10-13');
        $this->assertFalse($this->calendar()->isTahfizhEffectiveDay($this->classRoom, $date));
        $this->assertTrue($this->calendar()->isAdabEffectiveDay($date));
    }

    #[Test]
    public function adab_coordinator_can_only_change_adab_and_never_touches_tahfizh(): void
    {
        $this->markHoliday('2026-10-13', tahfizhOff: true, adabOff: false);
        $this->markClassHoliday('2026-10-15', $this->classRoom->id);

        // Mencoba juga menghapus libur Tahfizh & libur kelas -- harus diabaikan.
        $this->save($this->adabCoordinator, [
            'days' => ['2026-10-14' => ['adab' => 1, 'tahfizh' => 1]],
        ])->assertRedirect();

        $this->assertFalse($this->calendar()->isAdabEffectiveDay(Carbon::parse('2026-10-14')));
        $this->assertTrue($this->calendar()->isTahfizhEffectiveDay($this->classRoom, Carbon::parse('2026-10-14')), 'Koordinator Adab tidak boleh meliburkan Tahfizh.');
        $this->assertFalse($this->calendar()->isTahfizhEffectiveDay($this->classRoom, Carbon::parse('2026-10-13')), 'Libur Tahfizh lama tetap.');
        $this->assertSame([$this->classRoom->id], $this->calendar()->classDays(2026)['2026-10-15'] ?? null, 'Libur sebagian kelas tetap.');
    }

    #[Test]
    public function adab_coordinator_can_open_the_calendar_but_other_roles_cannot(): void
    {
        $this->actingAs($this->adabCoordinator)->get(route('academic-calendar.index', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertViewHas('permissions', fn ($p) => $p['edit_adab'] && ! $p['edit_tahfizh'] && ! $p['unlock']);

        $this->actingAs($this->teacherUser)->get(route('academic-calendar.index'))->assertForbidden();

        $pendamping = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'pendamping_adab'], ['display_name' => 'Pendamping Adab'])->id,
            'status' => 'active',
        ]);
        $this->actingAs($pendamping)->get(route('academic-calendar.index'))->assertForbidden();
    }

    #[Test]
    public function locked_tahfizh_month_rejects_changes_until_admin_unlocks(): void
    {
        $this->actingAs($this->admin)->post(route('academic-calendar.lock'), ['year' => 2026, 'month' => 10, 'scope' => 'tahfizh', 'action' => 'lock'])->assertRedirect();
        $this->assertTrue($this->calendar()->isMonthLocked(2026, 10, 'tahfizh'));

        // Tahfizh terkunci: perubahan Tahfizh ditolak, Adab masih boleh.
        $this->save($this->admin, ['days' => ['2026-10-13' => ['tahfizh' => 1, 'adab' => 1]]])->assertRedirect();
        $this->assertTrue($this->calendar()->isTahfizhEffectiveDay($this->classRoom, Carbon::parse('2026-10-13')));
        $this->assertFalse($this->calendar()->isAdabEffectiveDay(Carbon::parse('2026-10-13')));

        // Buka kunci, lalu Tahfizh bisa diubah.
        $this->actingAs($this->admin)->post(route('academic-calendar.lock'), ['year' => 2026, 'month' => 10, 'scope' => 'tahfizh', 'action' => 'unlock'])->assertRedirect();
        $this->save($this->admin, ['days' => ['2026-10-13' => ['tahfizh' => 1, 'adab' => 1]]]);
        $this->assertFalse($this->calendar()->isTahfizhEffectiveDay($this->classRoom, Carbon::parse('2026-10-13')));

        $this->assertTrue(AuditLog::where('auditable_label', 'Kunci Kalender')->where('action', 'created')->exists());
        $this->assertTrue(AuditLog::where('auditable_label', 'Kunci Kalender')->where('action', 'deleted')->exists());
    }

    #[Test]
    public function adab_coordinator_can_lock_adab_but_not_tahfizh_and_cannot_unlock(): void
    {
        $lock = fn (User $user, string $scope, string $action) => $this->actingAs($user)->post(route('academic-calendar.lock'), [
            'year' => 2026, 'month' => 10, 'scope' => $scope, 'action' => $action,
        ]);

        $lock($this->adabCoordinator, 'tahfizh', 'lock')->assertForbidden();
        $lock($this->adabCoordinator, 'adab', 'lock')->assertRedirect();
        $this->assertTrue($this->calendar()->isMonthLocked(2026, 10, 'adab'));

        // Adab terkunci: koordinator tidak bisa lagi mengubah, apalagi membuka kunci.
        $this->save($this->adabCoordinator, ['days' => ['2026-10-14' => ['adab' => 1]]])->assertForbidden();
        $lock($this->adabCoordinator, 'adab', 'unlock')->assertForbidden();

        $lock($this->admin, 'adab', 'unlock')->assertRedirect();
        $this->assertFalse($this->calendar()->isMonthLocked(2026, 10, 'adab'));
    }

    #[Test]
    public function adab_days_can_be_changed_by_admin_and_adab_coordinator_only(): void
    {
        $this->actingAs($this->admin)->post(route('academic-calendar.adab-days'), ['adab_days' => [1, 2, 3]])->assertRedirect();

        $this->assertSame([1, 2, 3], $this->calendar()->adabDays());
        $this->assertTrue($this->calendar()->isAdabEffectiveDay(Carbon::parse('2026-10-12')), 'Senin kini hari Adab.');
        $this->assertFalse($this->calendar()->isAdabEffectiveDay(Carbon::parse('2026-10-16')), 'Jumat bukan lagi hari Adab.');

        $this->actingAs($this->adabCoordinator)->post(route('academic-calendar.adab-days'), ['adab_days' => [2, 3, 4, 5]])->assertRedirect();
        $this->assertSame([2, 3, 4, 5], $this->calendar()->adabDays());

        $this->actingAs($this->teacherUser)->post(route('academic-calendar.adab-days'), ['adab_days' => [1]])->assertForbidden();
        $this->actingAs($this->admin)->post(route('academic-calendar.adab-days'), ['adab_days' => []])->assertSessionHasErrors('adab_days');
    }
}
