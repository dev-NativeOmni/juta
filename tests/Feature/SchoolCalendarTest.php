<?php

namespace Tests\Feature;

use App\Models\CalendarDay;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Setting;
use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kalender sekolah terpadu: aturan hari efektif Tahfizh & Adab di satu tempat,
 * dan pemindahan data libur lama dari Settings ke tabel calendar_days.
 */
class SchoolCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function calendar(): SchoolCalendar
    {
        return app(SchoolCalendar::class);
    }

    private function classRoom(string $name = 'XI Kalender'): ClassRoom
    {
        $program = Program::firstOrCreate(['name' => 'Program Kalender'], ['status' => 'active']);

        return ClassRoom::create(['program_id' => $program->id, 'name' => $name, 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
    }

    #[Test]
    public function unconfigured_year_uses_default_total_holidays(): void
    {
        $this->assertContains('2031-08-17', $this->calendar()->totalHolidays(2031));
        $this->assertFalse($this->calendar()->isAdabEffectiveDay(Carbon::parse('2027-08-17'))); // Selasa, 17 Agustus
    }

    #[Test]
    public function tahfizh_and_adab_follow_their_own_days_and_scopes(): void
    {
        $classRoom = $this->classRoom();
        $monday = Carbon::parse('2026-07-06');
        $tuesday = Carbon::parse('2026-07-07');

        // Senin: Tahfizh efektif, Adab tidak (Adab Selasa-Jumat).
        $this->assertTrue($this->calendar()->isTahfizhEffectiveDay($classRoom, $monday));
        $this->assertFalse($this->calendar()->isAdabEffectiveDay($monday));

        // Libur Tahfizh saja: Adab tetap berjalan.
        $this->markHoliday('2026-07-07', tahfizhOff: true, adabOff: false);
        $this->assertFalse($this->calendar()->isTahfizhEffectiveDay($classRoom, $tuesday));
        $this->assertTrue($this->calendar()->isAdabEffectiveDay($tuesday));

        // Libur Sebagian kelas: Adab tidak terpengaruh.
        $this->markClassHoliday('2026-07-08', $classRoom->id);
        $this->assertFalse($this->calendar()->isTahfizhEffectiveDay($classRoom, Carbon::parse('2026-07-08')));
        $this->assertTrue($this->calendar()->isAdabEffectiveDay(Carbon::parse('2026-07-08')));
    }

    #[Test]
    public function saving_a_month_keeps_other_months_and_freezes_defaults_on_first_save(): void
    {
        $classRoom = $this->classRoom();

        $this->calendar()->saveMonth(2030, 3, ['2030-03-10' => ['tahfizh_off' => true, 'adab_off' => true]], ['2030-03-11' => [$classRoom->id]]);

        $holidays = $this->calendar()->totalHolidays(2030);
        $this->assertContains('2030-03-10', $holidays);
        $this->assertContains('2030-08-17', $holidays, 'Libur bawaan bulan lain harus tetap ada setelah tahun pertama kali diatur.');
        $this->assertSame([$classRoom->id], $this->calendar()->classDays(2030)['2030-03-11']);

        // Mengosongkan Maret tidak menyentuh Agustus.
        $this->calendar()->saveMonth(2030, 3, [], []);
        $this->assertNotContains('2030-03-10', $this->calendar()->totalHolidays(2030));
        $this->assertContains('2030-08-17', $this->calendar()->totalHolidays(2030));
    }

    #[Test]
    public function migration_moves_legacy_settings_into_calendar_days(): void
    {
        $classRoom = $this->classRoom();
        Schema::drop('calendar_days');
        Setting::set('national_holidays_2026', json_encode(['2026-07-07', 'bukan-tanggal']));
        Setting::set('class_holidays_2026', json_encode(['2026-07-08' => [$classRoom->id, 99999]]));
        Setting::set('national_holidays_2029', json_encode([]));

        (require database_path('migrations/2026_09_24_000001_create_calendar_days_table.php'))->up();
        $this->calendar()->flush();

        $this->assertSame(['2026-07-07'], $this->calendar()->totalHolidays(2026));
        $this->assertSame(['2026-07-08' => [$classRoom->id]], $this->calendar()->classDays(2026), 'Kelas yang sudah dihapus dilewati.');
        $this->assertSame([], $this->calendar()->totalHolidays(2029), 'Tahun yang sengaja dikosongkan admin tidak kembali ke libur bawaan.');
        $this->assertSame(2, DB::table('calendar_days')->count());
        $this->assertFalse(CalendarDay::whereNotNull('class_room_id')->first()->adab_off);
    }
}
