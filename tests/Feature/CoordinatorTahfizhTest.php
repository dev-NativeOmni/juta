<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTahfizhTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_coordinator_tahfizh_read_only_access(): void
    {
        // 1. Create coordinator_tahfizh role & user
        $role = Role::updateOrCreate([
            'name' => 'coordinator_tahfizh',
        ], [
            'name' => 'coordinator_tahfizh',
            'display_name' => 'Koordinator Tahfizh',
        ]);

        $coordinator = User::factory()->create([
            'username' => 'koordinatortahfizh',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // Get seeded objects
        $student = Student::firstOrFail();
        $teacherProfile = TeacherProfile::firstOrFail();
        $surah = Surah::firstOrFail();

        // Create sample records
        $hafalan = HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherProfile->id,
            'surah_id' => $surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $murajaah = MurajaahRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherProfile->id,
            'surah_id' => $surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'overall_score' => 85,
            'reviewed_at' => now(),
        ]);

        $target = HafalanTarget::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherProfile->id,
            'surah_id' => $surah->id,
            'ayah' => 5,
            'target_date' => now()->addDays(7),
            'status' => 'planned',
        ]);

        // A. Verify Read-Only pages access
        $this->actingAs($coordinator)->get('/hafalan-records')->assertStatus(200);
        $this->actingAs($coordinator)->get("/hafalan-records/{$hafalan->id}")->assertStatus(200);

        $this->actingAs($coordinator)->get('/murajaah-records')->assertStatus(200);
        $this->actingAs($coordinator)->get("/murajaah-records/{$murajaah->id}")->assertStatus(200);

        $this->actingAs($coordinator)->get('/hafalan-targets')->assertStatus(200);
        $this->actingAs($coordinator)->get("/hafalan-targets/{$target->id}")->assertStatus(200);

        $this->actingAs($coordinator)->get('/progress')->assertStatus(200);
        $this->actingAs($coordinator)->get("/progress/{$student->id}")->assertStatus(200);

        $this->actingAs($coordinator)->get('/reports')->assertStatus(200);
        $this->actingAs($coordinator)->get("/reports/student/{$student->id}")->assertStatus(200);

        // B. Verify block on Writes (should get 403)
        // 1. Create page
        $this->actingAs($coordinator)->get('/hafalan-records/create')->assertStatus(403);
        $this->actingAs($coordinator)->get('/murajaah-records/create')->assertStatus(403);
        $this->actingAs($coordinator)->get('/hafalan-targets/create')->assertStatus(403);

        // 2. Store action
        $this->actingAs($coordinator)->post('/hafalan-records', [])->assertStatus(403);
        $this->actingAs($coordinator)->post('/murajaah-records', [])->assertStatus(403);
        $this->actingAs($coordinator)->post('/hafalan-targets', [])->assertStatus(403);

        // 3. Edit page
        $this->actingAs($coordinator)->get("/hafalan-records/{$hafalan->id}/edit")->assertStatus(403);
        $this->actingAs($coordinator)->get("/murajaah-records/{$murajaah->id}/edit")->assertStatus(403);
        $this->actingAs($coordinator)->get("/hafalan-targets/{$target->id}/edit")->assertStatus(403);

        // 4. Update action
        $this->actingAs($coordinator)->put("/hafalan-records/{$hafalan->id}", [])->assertStatus(403);
        $this->actingAs($coordinator)->put("/murajaah-records/{$murajaah->id}", [])->assertStatus(403);
        $this->actingAs($coordinator)->put("/hafalan-targets/{$target->id}", [])->assertStatus(403);

        // 5. Complete / Missed target actions
        $this->actingAs($coordinator)->patch("/hafalan-targets/{$target->id}/complete", [])->assertStatus(403);
        $this->actingAs($coordinator)->patch("/hafalan-targets/{$target->id}/mark-missed", [])->assertStatus(403);

        // 6. Delete action
        $this->actingAs($coordinator)->delete("/hafalan-records/{$hafalan->id}")->assertStatus(403);
        $this->actingAs($coordinator)->delete("/murajaah-records/{$murajaah->id}")->assertStatus(403);
        $this->actingAs($coordinator)->delete("/hafalan-targets/{$target->id}")->assertStatus(403);
    }
}
