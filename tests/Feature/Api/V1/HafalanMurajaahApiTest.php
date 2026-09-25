<?php

namespace Tests\Feature\Api\V1;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\QuranDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HafalanMurajaahApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CoreDataSeeder::class,
            QuranDataSeeder::class,
        ]);
    }

    public function test_user_can_list_and_get_hafalan_records(): void
    {
        $student = Student::first();
        $teacher = TeacherProfile::first();
        $surah = Surah::first();

        $hafalanRecord = HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'submitted_at' => now()->toDateString(),
        ]);

        $record = $hafalanRecord->surahs()->create([
            'surah_id' => $surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'score' => 90.00,
            'status' => 'passed',
        ]);

        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/hafalan-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'hafalan_records',
                ],
            ]);

        $this->assertCount(1, $response['data']['hafalan_records']);

        // Test show endpoint
        $responseShow = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/hafalan-records/'.$record->id);

        $responseShow->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'hafalan_record' => [
                        'id' => $record->id,
                        'score' => '90.00',
                    ],
                ],
            ]);
    }

    public function test_user_can_list_and_get_murajaah_records(): void
    {
        $student = Student::first();
        $teacher = TeacherProfile::first();
        $surah = Surah::first();

        $record = MurajaahRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'surah_id' => $surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'overall_score' => 85.50,
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/murajaah-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'murajaah_records',
                ],
            ]);

        $this->assertCount(1, $response['data']['murajaah_records']);

        // Test show endpoint
        $responseShow = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/murajaah-records/'.$record->id);

        $responseShow->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'murajaah_record' => [
                        'id' => $record->id,
                        'overall_score' => '85.50',
                    ],
                ],
            ]);
    }

    public function test_user_can_list_and_get_hafalan_targets(): void
    {
        $student = Student::first();
        $teacher = TeacherProfile::first();
        $surah = Surah::first();

        $target = HafalanTarget::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'surah_id' => $surah->id,
            'ayah' => 5,
            'target_date' => now()->addDays(2)->toDateString(),
            'status' => 'active',
        ]);

        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/hafalan-targets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'hafalan_targets',
                ],
            ]);

        $this->assertCount(1, $response['data']['hafalan_targets']);

        // Test show endpoint
        $responseShow = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/hafalan-targets/'.$target->id);

        $responseShow->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'hafalan_target' => [
                        'id' => $target->id,
                        'status' => 'active',
                    ],
                ],
            ]);
    }
}
