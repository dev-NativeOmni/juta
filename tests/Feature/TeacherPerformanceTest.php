<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_teacher_performance_requires_authentication(): void
    {
        $response = $this->get('/reports/teachers');
        $response->assertRedirect('/login');
    }

    public function test_teacher_performance_accessible_by_super_admin_admin_and_headmaster(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        // Ensure headmaster role exists
        $headmasterRole = Role::updateOrCreate(
            ['name' => 'headmaster'],
            ['name' => 'headmaster', 'display_name' => 'Kepala Sekolah']
        );
        $headmaster = User::factory()->create([
            'username' => 'kabagtahfizh',
            'role_id' => $headmasterRole->id,
            'status' => 'active',
        ]);

        $responseSuper = $this->actingAs($superAdmin)->get('/reports/teachers');
        $responseSuper->assertStatus(200);

        $responseAdmin = $this->actingAs($admin)->get('/reports/teachers');
        $responseAdmin->assertStatus(200);

        $responseHead = $this->actingAs($headmaster)->get('/reports/teachers');
        $responseHead->assertStatus(200);
    }

    public function test_teacher_performance_forbidden_for_teacher_and_student(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $student = User::where('username', 'santri')->first();

        $responseTeacher = $this->actingAs($teacher)->get('/reports/teachers');
        $responseTeacher->assertStatus(403);

        $responseStudent = $this->actingAs($student)->get('/reports/teachers');
        $responseStudent->assertStatus(403);
    }

    public function test_performance_calculation_formulation(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Find or create a teacher profile
        $teacherUser = User::where('username', 'guru')->first();
        $teacherProfile = TeacherProfile::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'phone' => '0812345678',
                'address' => 'Jl. Tahfidz No. 1',
            ]
        );

        $program = Program::create([
            'name' => 'Reguler',
            'status' => 'active',
        ]);
        $classRoom = ClassRoom::create([
            'name' => 'Kelas A',
            'program_id' => $program->id,
            'level' => '1',
        ]);

        $studentUser = User::factory()->create([
            'username' => 'santritest',
            'role_id' => User::where('username', 'santri')->first()->role_id,
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacherProfile->id,
            'name' => 'Santri Test',
            'student_number' => 'SNT-TEST-01',
            'gender' => 'male',
            'birth_date' => '2012-01-15',
            'status' => 'active',
        ]);

        $surah = Surah::create([
            'number' => 1,
            'name_ar' => 'الفاتحة',
            'name_latin' => 'Al-Fatihah',
            'total_ayah' => 7,
            'juz_start' => 1,
            'juz_end' => 1,
        ]);

        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        // Create 15 Hafalan Records and 15 Murajaah Records (Total 30 inputs -> Keaktifan score should be max 40 points)
        for ($i = 0; $i < 15; $i++) {
            $hafalanRecord = HafalanRecord::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'submitted_at' => now(),
            ]);
            $hafalanRecord->surahs()->create([
                'surah_id' => $surah->id,
                'ayah_start' => 1,
                'ayah_end' => 7,
                'score' => 90, // points
                'status' => 'passed',
            ]);
            MurajaahRecord::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'surah_id' => $surah->id,
                'ayah_start' => 1,
                'ayah_end' => 7,
                'fluency_score' => 80,
                'tajwid_score' => 80,
                'makhraj_score' => 80,
                'overall_score' => 80, // points
                'reviewed_at' => now(),
                'status' => 'passed',
            ]);
        }

        // Create targets: 4 completed, 1 active (Total 5 -> Target score should be 4/5 * 40 = 32 points)
        for ($i = 0; $i < 4; $i++) {
            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'surah_id' => $surah->id,
                'ayah' => 7,
                'status' => 'completed',
                'target_date' => now(),
            ]);
        }
        HafalanTarget::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherProfile->id,
            'surah_id' => $surah->id,
            'ayah' => 7,
            'status' => 'active',
            'target_date' => now(),
        ]);

        // Student average score: (90 + 80) / 2 = 85.0
        // Student score points (Max 20 points): 85.0 / 100 * 20 = 17.0 points

        // Expected total final score: 40 (Keaktifan) + 32 (Target) + 17 (Student Score) = 89.0 points
        // Expected Category: "Sangat Baik" (>= 85.0)

        $response = $this->actingAs($superAdmin)->get(route('reports.teachers', [
            'month' => $currentMonth,
            'year' => $currentYear,
        ]));

        $response->assertStatus(200);

        $data = $response->viewData('performanceData');
        $this->assertNotEmpty($data);

        $teacherData = collect($data)->firstWhere('teacher.id', $teacherProfile->id);
        $this->assertNotNull($teacherData);

        $this->assertEquals(30, $teacherData['total_inputs']);
        $this->assertEquals(40.0, $teacherData['keaktifan_score']);
        $this->assertEquals(5, $teacherData['total_targets']);
        $this->assertEquals(4, $teacherData['completed_targets']);
        $this->assertEquals(32.0, $teacherData['target_score']);
        $this->assertEquals(85.0, $teacherData['avg_student_score']);
        $this->assertEquals(17.0, $teacherData['student_score_points']);
        $this->assertEquals(89.0, $teacherData['final_score']);
        $this->assertEquals('Sangat Baik', $teacherData['category']);
    }
}
