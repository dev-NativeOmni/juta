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

class ClassScheduleTest extends TestCase
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
    }

    public function test_only_admin_and_superadmin_can_access_schedules_index(): void
    {
        $this->get(route('class-schedules.index'))->assertRedirect('/login');

        $this->actingAs($this->teacherUser)->get(route('class-schedules.index'))->assertStatus(403);

        $response = $this->actingAs($this->adminUser)->get(route('class-schedules.index'));
        $response->assertStatus(200);
        $response->assertViewHas('classRooms');
        $response->assertViewHas('scheduleBoard');
    }

    public function test_admin_can_update_classroom_schedules(): void
    {
        $this->assertEquals([1, 2, 3, 4, 5], $this->classRoom->tahfizh_days);

        $payload = [
            'schedules' => [
                3 => [
                    $this->classRoom->id => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('class-schedules.update'), $payload);
        $response->assertRedirect(route('class-schedules.index'));

        $this->classRoom->refresh();
        $this->assertEquals([3], $this->classRoom->tahfizh_days);
    }

    public function test_holidays_are_excluded_from_spreadsheet_and_reports(): void
    {
        $year = (int) date('Y');
        Setting::set("national_holidays_{$year}", json_encode([
            "{$year}-08-17",
            "{$year}-08-20",
        ]));

        $holidays = Setting::getNationalHolidays($year);
        $this->assertContains("{$year}-08-17", $holidays);
        $this->assertContains("{$year}-08-20", $holidays);

        // Create a student in the classroom to bypass empty student abort check
        $student = Student::create([
            'class_room_id' => $this->classRoom->id,
            'name' => 'Santri Coba',
            'nis' => '12345',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', [
            'class_room_id' => $this->classRoom->id,
            'month' => "{$year}-08",
        ]));
        $response->assertStatus(200);
        $response->assertViewHas('dates');

        $dates = $response->viewData('dates');
        $this->assertNotContains("{$year}-08-17", $dates);
        $this->assertNotContains("{$year}-08-20", $dates);
    }
}
