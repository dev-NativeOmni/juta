<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\AdabRecord;
use Illuminate\Support\Facades\DB;

class AdabControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adab_index_performance()
    {
        // Setup supervisor role and user
        $supervisorRole = Role::where('name', 'supervisor')->first();
        if (! $supervisorRole) {
            $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        }
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'testsupervisor',
            'status' => 'active',
        ]);

        $classRoom = ClassRoom::create(['name' => 'Class 1', 'level' => '10']);

        // Create 20 students
        $students = [];
        for ($i = 0; $i < 20; $i++) {
            $studentUser = User::factory()->create(['username' => 'student' . $i, 'status' => 'active']);
            $student = Student::create([
                'name' => 'Student ' . $i,
                'student_number' => 'S' . $i,
                'class_room_id' => $classRoom->id,
                'user_id' => $studentUser->id,
            ]);
            $students[] = $student;

            AdabRecord::create([
                'student_id' => $student->id,
                'assessment_date' => now()->toDateString(),
                'answers' => ['cat_0' => [1, 1], 'cat_1' => [1, 0]],
                'total_score' => 75
            ]);
        }

        DB::enableQueryLog();

        $response = $this->actingAs($supervisor)->get(route('adab.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $adabQueries = 0;

        $totalQueries = count($queries);

        foreach ($queries as $q) {
            if (strpos($q['query'], 'adab_records') !== false && strpos($q['query'], 'student_id') !== false && strpos($q['query'], 'assessment_date') !== false && strpos($q['query'], 'limit') !== false) {
                $adabQueries++;
            }
        }

        echo "Total Queries: " . $totalQueries . "\n";
        echo "AdabRecord Queries: " . $adabQueries . "\n";

        $this->assertEquals(0, $adabQueries, "There should be no N+1 queries for AdabRecord in the loop. Current count: $adabQueries");
    }
}
