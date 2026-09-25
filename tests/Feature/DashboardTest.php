<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function createHafalanRecord(array $overrides = []): HafalanRecord
    {
        $record = HafalanRecord::create([
            'student_id' => $overrides['student_id'] ?? $this->student->id,
            'teacher_id' => $overrides['teacher_id'] ?? $this->teacherProfile->id,
            'submitted_at' => $overrides['submitted_at'] ?? now(),
        ]);

        $record->surahs()->create([
            'surah_id' => $overrides['surah_id'] ?? $this->surah->id,
            'ayah_start' => $overrides['ayah_start'] ?? 1,
            'ayah_end' => $overrides['ayah_end'] ?? 7,
            'status' => $overrides['status'] ?? 'passed',
            'score' => $overrides['score'] ?? null,
        ]);

        return $record;
    }

    // =========================================================================
    // AKSES DASHBOARD — GUEST
    // =========================================================================

    #[Test]
    public function guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');
    }

    // =========================================================================
    // AKSES DASHBOARD — REDIRECT PER ROLE
    // =========================================================================

    #[Test]
    public function super_admin_is_redirected_to_super_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        // Route 'dashboard' adalah redirect ke dashboard spesifik role
        $response->assertRedirect(route('super-admin.dashboard'));
    }

    #[Test]
    public function admin_is_redirected_to_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function teacher_is_redirected_to_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('dashboard'));

        $response->assertRedirect(route('teacher.dashboard'));
    }

    #[Test]
    public function parent_is_redirected_to_parent_dashboard(): void
    {
        $response = $this->actingAs($this->parentUser)->get(route('dashboard'));

        $response->assertRedirect(route('parent.dashboard'));
    }

    #[Test]
    public function student_is_redirected_to_student_dashboard(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('dashboard'));

        $response->assertRedirect(route('student.dashboard'));
    }

    // =========================================================================
    // AKSES DASHBOARD DETAIL — VIEW RENDER PER ROLE
    // =========================================================================

    #[Test]
    public function super_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.admin');
    }

    #[Test]
    public function admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.admin');
    }

    #[Test]
    public function teacher_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('teacher.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.teacher');
    }

    #[Test]
    public function parent_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->parentUser)->get(route('parent.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.parent');
    }

    #[Test]
    public function parent_dashboard_displays_child_latest_memorized_surahs_at_top_with_dates(): void
    {
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 10,
            'status' => 'passed',
            'score' => 95.0,
            'submitted_at' => '2026-09-08',
        ]);

        $response = $this->actingAs($this->parentUser)->get(route('parent.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Surah Terakhir yang Dihafal Ananda');
        $response->assertSee('QS. '.$this->surah->name_latin);
        $response->assertSee('Ayat 1 - 10');
        $response->assertSee('08 September 2026');
    }

    #[Test]
    public function student_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.student');
    }

    #[Test]
    public function student_dashboard_displays_latest_memorized_surahs_at_top_with_dates(): void
    {
        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 10,
            'status' => 'passed',
            'score' => 95.0,
            'submitted_at' => '2026-09-08',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Surah Terakhir yang Dihafal');
        $response->assertSee('QS. '.$this->surah->name_latin);
        $response->assertSee('Ayat 1 - 10');
        $response->assertSee('08 September 2026');
    }

    #[Test]
    public function student_dashboard_displays_ummi_memorized_surahs_at_top_when_hafalan_records_empty(): void
    {
        $ummiRecord = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-09-07',
            'ummi_jilid' => 'Jilid 1',
            'ummi_halaman' => '10',
            'nilai' => '90',
        ]);

        $ummiRecord->surahs()->create([
            'surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-15',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Surah Terakhir yang Dihafal');
        $response->assertSee('QS. '.$this->surah->name_latin);
        $response->assertSee('Ayat 1 - 15');
        $response->assertSee('07 September 2026');
    }

    // =========================================================================
    // AKUN NONAKTIF
    // =========================================================================

    #[Test]
    public function inactive_user_is_logged_out_and_redirected_to_login(): void
    {
        // Nonaktifkan admin
        $this->admin->update(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        // Harus dilogout dan dikembalikan ke login dengan pesan error
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }
}
