<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $teacherUser;
    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->teacherUser = User::where('username', 'guru')->first();

        $program = Program::create([
            'name' => 'Program Regular',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $this->classRoom = ClassRoom::create([
            'name' => 'Kelas X Coba',
            'program_id' => $program->id,
            'status' => 'active',
        ]);

        Student::create([
            'class_room_id' => $this->classRoom->id,
            'name' => 'Santri Coba',
            'nis' => '12345',
            'status' => 'active',
        ]);
    }

    public function test_only_admin_and_superadmin_can_access_academic_calendar(): void
    {
        // Guest is redirected
        $this->get(route('academic-calendar.index'))->assertRedirect('/login');

        // Teacher is forbidden (403)
        $this->actingAs($this->teacherUser)->get(route('academic-calendar.index'))->assertStatus(403);

        // Admin can access
        $response = $this->actingAs($this->adminUser)->get(route('academic-calendar.index'));
        $response->assertStatus(200);
        $response->assertViewHas('gridDates');
        $response->assertViewHas('holidays');
    }

    public function test_admin_can_toggle_and_save_holidays_with_month_merging(): void
    {
        $year = (int) date('Y');

        Setting::set("national_holidays_{$year}", json_encode(["{$year}-12-25"]));
        Setting::set("class_holidays_{$year}", json_encode(["{$year}-12-25" => [$this->classRoom->id]]));

        $payload = [
            'year' => $year,
            'month' => 8,
            'holidays' => [
                "{$year}-08-17",
            ],
            'class_holidays' => [
                "{$year}-08-20" => [$this->classRoom->id]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('academic-calendar.update'), $payload);
        $response->assertRedirect(route('academic-calendar.index', ['year' => $year, 'month' => 8]));

        $holidays = Setting::getNationalHolidays($year);
        $this->assertContains("{$year}-08-17", $holidays);
        $this->assertContains("{$year}-12-25", $holidays);

        $classHolidays = json_decode(Setting::get("class_holidays_{$year}"), true);
        $this->assertArrayHasKey("{$year}-08-20", $classHolidays);
        $this->assertContains($this->classRoom->id, $classHolidays["{$year}-08-20"]);
        $this->assertArrayHasKey("{$year}-12-25", $classHolidays);

        $responseSpreadsheet = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', [
            'class_room_id' => $this->classRoom->id,
            'month' => "{$year}-08",
        ]));
        $responseSpreadsheet->assertStatus(200);
        $responseSpreadsheet->assertViewHas('dates');

        $dates = $responseSpreadsheet->viewData('dates');
        $this->assertNotContains("{$year}-08-17", $dates);
        $this->assertNotContains("{$year}-08-20", $dates);
    }
}
