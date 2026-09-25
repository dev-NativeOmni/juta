<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class HafalanRecordTest extends TestCase
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
            'notes' => $overrides['notes'] ?? null,
            'submitted_at' => $overrides['submitted_at'] ?? now(),
        ]);

        $record->surahs()->create([
            'surah_id' => $overrides['surah_id'] ?? $this->surah->id,
            'ayah_start' => $overrides['ayah_start'] ?? 1,
            'ayah_end' => $overrides['ayah_end'] ?? 7,
            'submission_type' => $overrides['submission_type'] ?? 'new',
            'status' => $overrides['status'] ?? 'passed',
            'score' => $overrides['score'] ?? null,
        ]);

        return $record;
    }

    // =========================================================================
    // AKSES HALAMAN (ADMIN)
    // =========================================================================

    #[Test]
    public function admin_can_view_hafalan_record_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-records.index'));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.index');
    }

    #[Test]
    public function admin_can_view_create_hafalan_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-records.create'));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.create');
    }

    #[Test]
    public function teacher_can_view_hafalan_record_index(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_is_redirected_from_hafalan_index(): void
    {
        $response = $this->get(route('hafalan-records.index'));

        $response->assertRedirect('/login');
    }

    // =========================================================================
    // TAMBAH DATA HAFALAN (ADMIN)
    // =========================================================================

    #[Test]
    public function admin_can_store_hafalan_record(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'notes' => 'Hafalan perdana Al-Fatihah.',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('hafalan_record_surahs', [
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function teacher_can_store_hafalan_record_for_own_student(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'continuation',
            'score' => 80,
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
        ]);
        $this->assertDatabaseHas('hafalan_record_surahs', [
            'ayah_start' => 1,
            'ayah_end' => 3,
        ]);
    }

    // =========================================================================
    // VALIDASI INPUT
    // =========================================================================

    #[Test]
    public function store_fails_when_ayah_end_exceeds_total_ayah(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, // total_ayah = 7
            'ayah_start' => 1,
            'ayah_end' => 999, // melebihi batas
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
        $this->assertDatabaseCount('hafalan_records', 0);
    }

    #[Test]
    public function store_fails_when_ayah_end_less_than_ayah_start(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 5,
            'ayah_end' => 2, // lebih kecil dari start
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
    }

    #[Test]
    public function store_fails_with_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'invalid_status', // tidak valid
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('status');
    }

    #[Test]
    public function store_fails_when_required_fields_missing(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), []);

        $response->assertSessionHasErrors(['student_id', 'surah_id', 'ayah_start', 'ayah_end', 'status', 'submitted_at']);
    }

    // =========================================================================
    // LIHAT, EDIT, UPDATE, HAPUS
    // =========================================================================

    #[Test]
    public function admin_can_view_hafalan_record_detail(): void
    {
        $record = $this->createHafalanRecord();

        $response = $this->actingAs($this->admin)->get(route('hafalan-records.show', $record));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.show');
        $response->assertViewHas('hafalanRecord');
    }

    #[Test]
    public function admin_can_update_hafalan_record(): void
    {
        $record = $this->createHafalanRecord(['status' => 'repeat']);

        $response = $this->actingAs($this->admin)->put(route('hafalan-records.update', $record), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_ids' => [$this->surah->id],
            'ayah_starts' => [1],
            'ayah_ends' => [7],
            'submission_types' => ['revision'],
            'scores' => [95],
            'statuses' => ['passed'], // diubah
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertDatabaseHas('hafalan_record_surahs', [
            'hafalan_record_id' => $record->id,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function admin_can_delete_hafalan_record(): void
    {
        $record = $this->createHafalanRecord();

        $response = $this->actingAs($this->admin)->delete(route('hafalan-records.destroy', $record));

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertSoftDeleted('hafalan_records', ['id' => $record->id]);
    }

    // =========================================================================
    // OTORISASI GURU
    // =========================================================================

    #[Test]
    public function teacher_cannot_delete_other_teachers_record(): void
    {
        // Buat guru lain
        $otherTeacherUser = User::factory()->create([
            'role_id' => $this->teacherUser->role_id,
            'status' => 'active',
        ]);
        $otherTeacher = TeacherProfile::create([
            'user_id' => $otherTeacherUser->id,
            'employee_number' => 'GURU-999',
            'phone' => '089900009999',
        ]);

        // Record milik guru lain
        $record = $this->createHafalanRecord([
            'teacher_id' => $otherTeacher->id, // guru lain
            'ayah_start' => 1,
            'ayah_end' => 3,
        ]);

        $response = $this->actingAs($this->teacherUser)->delete(route('hafalan-records.destroy', $record));

        $response->assertStatus(403);
        $this->assertNotSoftDeleted('hafalan_records', ['id' => $record->id]);
    }

    // =========================================================================
    // FILTER / SEARCH
    // =========================================================================

    #[Test]
    public function index_can_be_filtered_by_status(): void
    {
        $this->createHafalanRecord(['ayah_start' => 1, 'ayah_end' => 3]);

        $response = $this->actingAs($this->admin)
            ->get(route('hafalan-records.index', ['status' => 'passed']));

        $response->assertStatus(200);
        $response->assertViewHas('hafalanRecords', fn ($records) => $records->total() >= 1);
    }

    #[Test]
    public function admin_can_store_multiple_hafalan_records_at_once(): void
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_ids' => [$this->surah->id, $surah2->id],
            'ayah_starts' => [1, 5],
            'ayah_ends' => [7, 10],
            'submission_types' => ['new', 'continuation'],
            'scores' => [95, 85],
            'statuses' => ['passed', 'repeat'],
            'notes' => 'Multi setoran.',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('hafalan_records', 1);

        $this->assertDatabaseHas('hafalan_record_surahs', [
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 95.00,
            'status' => 'passed',
        ]);

        $this->assertDatabaseHas('hafalan_record_surahs', [
            'surah_id' => $surah2->id,
            'ayah_start' => 5,
            'ayah_end' => 10,
            'submission_type' => 'continuation',
            'score' => 85.00,
            'status' => 'repeat',
        ]);
    }

    // =========================================================================
    // RENDER: MULTI-SURAH SHOW / EDIT / LIST PAGES
    // =========================================================================

    #[Test]
    public function show_and_edit_pages_render_all_surahs_of_a_multi_surah_record(): void
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $record = $this->createHafalanRecord(['ayah_start' => 1, 'ayah_end' => 7]);
        $record->surahs()->create([
            'surah_id' => $surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 88,
        ]);

        $showResponse = $this->actingAs($this->admin)->get(route('hafalan-records.show', $record));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($this->surah->name_latin);
        $showResponse->assertSee($surah2->name_latin);

        $editResponse = $this->actingAs($this->admin)->get(route('hafalan-records.edit', $record));
        $editResponse->assertStatus(200);
        $editResponse->assertSee((string) $this->surah->id, false);
        $editResponse->assertSee((string) $surah2->id, false);
    }

    #[Test]
    public function reports_index_renders_multi_surah_record_correctly(): void
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $record = $this->createHafalanRecord(['ayah_start' => 1, 'ayah_end' => 7]);
        $record->surahs()->create([
            'surah_id' => $surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 88,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee($this->surah->name_latin);
        $response->assertSee($surah2->name_latin);
    }
}
