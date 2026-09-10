<?php

namespace Tests\Feature;

use App\Models\MurajaahRecord;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class MurajaahRecordTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    // =========================================================================
    // AKSES HALAMAN
    // =========================================================================

    #[Test]
    public function admin_can_view_murajaah_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('murajaah-records.index'));

        $response->assertStatus(200);
        $response->assertViewIs('murajaah-records.index');
    }

    #[Test]
    public function teacher_can_view_murajaah_index(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('murajaah-records.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_is_redirected_from_murajaah_index(): void
    {
        $this->get(route('murajaah-records.index'))->assertRedirect('/login');
    }

    // =========================================================================
    // TAMBAH DATA MURAJAAH
    // =========================================================================

    #[Test]
    public function admin_can_store_murajaah_record(): void
    {
        $response = $this->actingAs($this->admin)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 85,
            'tajwid_score' => 90,
            'makhraj_score' => 80,
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('murajaah-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('murajaah_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function teacher_can_store_murajaah_for_own_student(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 75,
            'tajwid_score' => 80,
            'makhraj_score' => 78,
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('murajaah-records.index'));

        $this->assertDatabaseHas('murajaah_records', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
        ]);
    }

    // =========================================================================
    // AUTO-KALKULASI OVERALL SCORE
    // =========================================================================

    #[Test]
    public function overall_score_is_auto_calculated_from_component_scores(): void
    {
        $this->actingAs($this->admin)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 90,
            'tajwid_score' => 90,
            'makhraj_score' => 90,
            // overall_score tidak dikirim → harus dihitung otomatis
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $record = MurajaahRecord::first();
        $this->assertNotNull($record);
        // (90+90+90)/3 = 90.00
        $this->assertEquals('90.00', $record->overall_score);
    }

    #[Test]
    public function overall_score_is_not_overwritten_when_already_provided(): void
    {
        $this->actingAs($this->admin)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 80,
            'tajwid_score' => 80,
            'makhraj_score' => 80,
            'overall_score' => 99, // disediakan manual
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $record = MurajaahRecord::first();
        // Nilai 99 yang dikirim harus dipakai (tidak di-override)
        $this->assertEquals('99.00', $record->overall_score);
    }

    // =========================================================================
    // VALIDASI INPUT
    // =========================================================================

    #[Test]
    public function store_fails_when_ayah_end_exceeds_total_ayah(): void
    {
        $response = $this->actingAs($this->admin)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, // total_ayah = 7
            'ayah_start' => 1,
            'ayah_end' => 100, // melebihi batas
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
    }

    #[Test]
    public function store_fails_when_score_out_of_range(): void
    {
        $response = $this->actingAs($this->admin)->post(route('murajaah-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 150, // out of range (max 100)
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('fluency_score');
    }

    // =========================================================================
    // LIHAT, EDIT, UPDATE, HAPUS
    // =========================================================================

    #[Test]
    public function admin_can_view_murajaah_detail(): void
    {
        $record = MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('murajaah-records.show', $record));

        $response->assertStatus(200);
        $response->assertViewIs('murajaah-records.show');
    }

    #[Test]
    public function admin_can_update_murajaah_record(): void
    {
        $record = MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'repeat',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('murajaah-records.update', $record), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'fluency_score' => 95,
            'tajwid_score' => 95,
            'makhraj_score' => 95,
            'status' => 'passed',
            'reviewed_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('murajaah-records.index'));
        $this->assertDatabaseHas('murajaah_records', ['id' => $record->id, 'status' => 'passed']);
    }

    #[Test]
    public function admin_can_soft_delete_murajaah_record(): void
    {
        $record = MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('murajaah-records.destroy', $record));

        $response->assertRedirect(route('murajaah-records.index'));
        $this->assertSoftDeleted('murajaah_records', ['id' => $record->id]);
    }

    // =========================================================================
    // OTORISASI GURU
    // =========================================================================

    #[Test]
    public function teacher_cannot_edit_other_teachers_murajaah(): void
    {
        $otherTeacherUser = User::factory()->create([
            'role_id' => $this->teacherUser->role_id,
            'status' => 'active',
        ]);
        $otherTeacher = TeacherProfile::create([
            'user_id' => $otherTeacherUser->id,
            'employee_number' => 'GURU-888',
            'phone' => '088800008888',
        ]);

        $record = MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $otherTeacher->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'reviewed_at' => now(),
        ]);

        // teacherUser mencoba edit record milik otherTeacher
        $response = $this->actingAs($this->teacherUser)->get(route('murajaah-records.edit', $record));

        $response->assertStatus(403);
    }

    #[Test]
    public function teacher_can_fast_store_single_surah(): void
    {
        $response = $this->actingAs($this->teacherUser)->postJson(route('murajaah-records.fast-store'), [
            'reviewed_at' => now()->toDateString(),
            'entries' => [
                [
                    'student_id' => $this->student->id,
                    'surah_id' => $this->surah->id,
                    'surah_end_id' => $this->surah->id,
                    'ayah_start' => 1,
                    'ayah_end' => 7,
                    'score' => 90,
                    'notes' => 'Lancar sekali',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('murajaah_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'overall_score' => 90,
            'status' => 'passed',
            'notes' => 'Lancar sekali',
        ]);
    }

    #[Test]
    public function fast_store_splits_cross_surah_murajaah(): void
    {
        $surah2 = \App\Models\Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $response = $this->actingAs($this->teacherUser)->postJson(route('murajaah-records.fast-store'), [
            'reviewed_at' => now()->toDateString(),
            'entries' => [
                [
                    'student_id' => $this->student->id,
                    'surah_id' => $this->surah->id, // Surah 1 (7 ayat)
                    'surah_end_id' => $surah2->id,   // Surah 2
                    'ayah_start' => 3,
                    'ayah_end' => 25,
                    'score' => 85,
                    'notes' => 'Cross surah murajaah test',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Should create 2 records:
        // Record 1: Surah 1, Ayah 3-7
        // Record 2: Surah 2, Ayah 1-25
        $this->assertDatabaseHas('murajaah_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 3,
            'ayah_end' => 7,
            'overall_score' => 85,
            'status' => 'passed',
        ]);

        $this->assertDatabaseHas('murajaah_records', [
            'student_id' => $this->student->id,
            'surah_id' => $surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 25,
            'overall_score' => 85,
            'status' => 'passed',
        ]);
    }
}
