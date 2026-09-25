<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentParentWebAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CoreDataSeeder::class,
        ]);
    }

    public function test_student_visiting_progress_is_redirected_to_their_own_detail_page(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $student = Student::where('user_id', $studentUser->id)->first();

        $response = $this->actingAs($studentUser)->get('/progress');

        $response->assertRedirect('/progress/'.$student->id);
    }

    public function test_student_visiting_reports_is_redirected_to_their_own_report_page(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $student = Student::where('user_id', $studentUser->id)->first();

        $response = $this->actingAs($studentUser)->get('/reports');

        $response->assertRedirect('/reports/student/'.$student->id);
    }

    public function test_student_cannot_view_another_student_progress(): void
    {
        $studentUser = User::where('username', 'santri')->first();

        // Create another student
        $student2User = User::factory()->create([
            'role_id' => $studentUser->role_id,
            'status' => 'active',
        ]);
        $student2 = Student::create([
            'student_number' => 'SNT-002',
            'user_id' => $student2User->id,
            'class_room_id' => ClassRoom::first()->id,
            'teacher_id' => TeacherProfile::first()->id,
            'name' => 'Santri Lain',
            'gender' => 'male',
            'birth_date' => '2012-01-15',
            'status' => 'active',
        ]);

        $response = $this->actingAs($studentUser)->get('/progress/'.$student2->id);

        $response->assertStatus(403);
    }

    public function test_parent_with_one_child_is_redirected_to_their_childs_pages(): void
    {
        $parentUser = User::where('username', 'orangtua')->first();
        $student = Student::where('student_number', 'SNT-001')->first();

        // Verify the parent only has 1 child
        $parentProfile = ParentProfile::where('user_id', $parentUser->id)->first();
        $this->assertEquals(1, $parentProfile->students()->count());

        $responseProgress = $this->actingAs($parentUser)->get('/progress');
        $responseProgress->assertRedirect('/progress/'.$student->id);

        $responseReports = $this->actingAs($parentUser)->get('/reports');
        $responseReports->assertRedirect('/reports/student/'.$student->id);

        $responseAdab = $this->actingAs($parentUser)->get('/adab');
        $responseAdab->assertRedirect('/adab/student/'.$student->id);
    }

    public function test_parent_and_student_cannot_view_cross_school_adab_chart(): void
    {
        $parentUser = User::where('username', 'orangtua')->first();
        $studentUser = User::where('username', 'santri')->first();

        $this->actingAs($parentUser)->get('/adab/chart')->assertRedirect('/adab');
        $this->actingAs($studentUser)->get('/adab/chart')->assertRedirect('/adab');
    }

    public function test_parent_and_student_cannot_view_cross_school_student_points_chart(): void
    {
        $parentUser = User::where('username', 'orangtua')->first();
        $studentUser = User::where('username', 'santri')->first();

        $this->actingAs($parentUser)->get('/student-points/chart')->assertRedirect('/student-points');
        $this->actingAs($studentUser)->get('/student-points/chart')->assertRedirect('/student-points');
    }

    public function test_parent_dashboard_renders_360_degree_monitoring_hub(): void
    {
        $parentUser = User::where('username', 'orangtua')->first();
        $response = $this->actingAs($parentUser)->get('/parent/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Portal Khusus Wali Murid');
        $response->assertSee('Tahfizh Al-Qur\'an');
        $response->assertSee('Karakter &amp; Adab', false);
        $response->assertSee('Kedisiplinan &amp; Prestasi', false);
    }

    public function test_student_and_parent_cannot_export_or_download_or_print(): void
    {
        $parentUser = User::where('username', 'orangtua')->first();
        $studentUser = User::where('username', 'santri')->first();
        $student = Student::where('student_number', 'SNT-001')->first();

        // 1. Export CSV laporan
        $this->actingAs($parentUser)->get('/reports/export/csv')->assertStatus(403);
        $this->actingAs($studentUser)->get('/reports/export/csv')->assertStatus(403);
        $this->actingAs($parentUser)->get('/reports/student/'.$student->id.'/export/csv')->assertStatus(403);
        $this->actingAs($studentUser)->get('/reports/student/'.$student->id.'/export/csv')->assertStatus(403);

        // 2. Cetak / Simpan PDF rapor digital
        $this->actingAs($parentUser)->get('/digital-reports/student/'.$student->id.'/print')->assertStatus(403);
        $this->actingAs($studentUser)->get('/digital-reports/student/'.$student->id.'/print')->assertStatus(403);

        // 3. Cetak Kartu UMMI
        $this->actingAs($parentUser)->get('/hafalan-records/student/'.$student->id.'/ummi-card')->assertStatus(403);
        $this->actingAs($studentUser)->get('/hafalan-records/student/'.$student->id.'/ummi-card')->assertStatus(403);

        // 4. Pastikan tombol export / cetak tidak muncul di view digital-reports.show
        $viewReport = $this->actingAs($studentUser)->get('/digital-reports/student/'.$student->id);
        $viewReport->assertDontSee('Cetak / Simpan PDF');
    }
}
