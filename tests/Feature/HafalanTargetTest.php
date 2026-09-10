<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class HafalanTargetTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    // =========================================================================
    // AKSES HALAMAN (MULTI-ROLE)
    // =========================================================================

    #[Test]
    public function admin_can_view_target_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-targets.index');
    }

    #[Test]
    public function teacher_can_view_target_index(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-targets.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function parent_cannot_access_hafalan_target_management(): void
    {
        // Route hafalan-targets hanya untuk super_admin, admin, teacher
        // Parent mendapatkan 403 dari middleware CheckRole
        $response = $this->actingAs($this->parentUser)->get(route('hafalan-targets.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function student_cannot_access_hafalan_target_management(): void
    {
        // Route hafalan-targets hanya untuk super_admin, admin, teacher
        // Student mendapatkan 403 dari middleware CheckRole
        $response = $this->actingAs($this->studentUser)->get(route('hafalan-targets.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_is_redirected_from_target_index(): void
    {
        $this->get(route('hafalan-targets.index'))->assertRedirect('/login');
    }

    // =========================================================================
    // BUAT TARGET
    // =========================================================================

    #[Test]
    public function admin_can_create_target(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-targets.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Target test Al-Fatihah.',
        ]);

        $response->assertRedirect(route('hafalan-targets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hafalan_targets', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
        ]);
    }

    #[Test]
    public function teacher_can_create_target_for_own_student(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'target_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-targets.index'));
        $this->assertDatabaseHas('hafalan_targets', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
        ]);
    }

    // =========================================================================
    // VALIDASI INPUT
    // =========================================================================

    #[Test]
    public function store_fails_when_target_date_missing(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-targets.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            // target_date tidak ada
        ]);

        $response->assertSessionHasErrors('target_date');
    }

    #[Test]
    public function store_fails_when_ayah_end_exceeds_surah_limit(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-targets.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id, // total_ayah = 7
            'ayah_start' => 1,
            'ayah_end' => 999, // melebihi batas
            'target_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
    }

    #[Test]
    public function store_fails_when_student_not_visible_to_teacher(): void
    {
        // Buat santri lain yang tidak ada di bawah guru ini
        $anotherStudent = Student::class;
        // Gunakan student milik guru lain (tidak ada teacher_id yang cocok)
        // Cukup gunakan student_id fiktif
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.store'), [
            'student_id' => 9999, // tidak ada
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'target_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    // =========================================================================
    // LIHAT DETAIL, EDIT, UPDATE
    // =========================================================================

    #[Test]
    public function admin_can_view_target_detail(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(7),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.show', $target));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-targets.show');
    }

    #[Test]
    public function admin_can_update_target(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(7),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('hafalan-targets.update', $target), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(14)->toDateString(),
            'status' => 'active',
            'notes' => 'Update target test.',
        ]);

        $response->assertRedirect(route('hafalan-targets.index'));
        $this->assertDatabaseHas('hafalan_targets', [
            'id' => $target->id,
            'status' => 'active',
        ]);
    }

    // =========================================================================
    // AKSI COMPLETE & MARK MISSED
    // =========================================================================

    #[Test]
    public function admin_can_mark_target_as_completed(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(7),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('hafalan-targets.complete', $target));

        $response->assertRedirect();
        $this->assertDatabaseHas('hafalan_targets', [
            'id' => $target->id,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function admin_can_mark_target_as_missed(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->subDays(3), // sudah lewat
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('hafalan-targets.mark-missed', $target));

        $response->assertRedirect();
        $this->assertDatabaseHas('hafalan_targets', [
            'id' => $target->id,
            'status' => 'missed',
        ]);
    }

    // =========================================================================
    // HAPUS TARGET
    // =========================================================================

    #[Test]
    public function admin_can_delete_target(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->addDays(7),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('hafalan-targets.destroy', $target));

        $response->assertRedirect(route('hafalan-targets.index'));
        $this->assertSoftDeleted('hafalan_targets', ['id' => $target->id]);
    }

    // =========================================================================
    // OVERDUE ATTRIBUTE
    // =========================================================================

    #[Test]
    public function target_is_overdue_when_active_and_date_passed(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->subDays(5), // sudah lewat
            'status' => 'active',
        ]);

        $this->assertTrue($target->is_overdue);
    }

    #[Test]
    public function target_is_not_overdue_when_completed(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => now()->subDays(2),
            'status' => 'completed',
        ]);

        $this->assertFalse($target->is_overdue);
    }

    // =========================================================================
    // BULK REGULER & BULK UMMI TARGETS
    // =========================================================================

    #[Test]
    public function teacher_can_store_bulk_reguler_targets(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.store-bulk-reguler'), [
            'class_room_id' => $this->student->class_room_id,
            'targets' => [
                [
                    'student_id' => $this->student->id,
                    'surah_id' => $this->surah->id,
                    'ayah_start' => 1,
                    'ayah_end' => 10,
                    'target_date' => now()->addDays(5)->toDateString(),
                    'notes' => 'Catatan Reguler Test',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hafalan_targets', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
        ]);
    }

    #[Test]
    public function teacher_can_store_bulk_ummi_targets_only_for_grade_10(): void
    {
        // Student 1 (Grade 10)
        $this->student->classRoom->update(['name' => 'X E1', 'level' => '10']);

        // Student 2 (Grade 11 under same teacher)
        $grade11Class = \App\Models\ClassRoom::create([
            'name' => 'XI F3',
            'level' => '11',
        ]);
        $grade11Student = \App\Models\Student::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'teacher_id' => $this->teacherProfile->id,
            'class_room_id' => $grade11Class->id,
            'name' => 'Grade 11 Student',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.store-bulk-ummi'), [
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 2',
            'halaman_peraga' => 'Hal 10',
            'halaman_buku' => 'Hal 15',
            'surah_id' => $this->surah->id,
            'target_date' => now()->addMonth()->toDateString(),
            'notes' => 'Catatan Ummi Test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Grade 10 student should have Ummi target
        $this->assertDatabaseHas('hafalan_targets', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 2',
        ]);

        // Grade 11 student should NOT have Ummi target
        $this->assertDatabaseMissing('hafalan_targets', [
            'student_id' => $grade11Student->id,
            'ummi_jilid' => 'Jilid 2',
        ]);
    }

    #[Test]
    public function teacher_can_bulk_complete_targets(): void
    {
        $target1 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => 'active',
        ]);

        $target2 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 6,
            'ayah_end' => 10,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.bulk-complete'), [
            'target_ids' => [$target1->id, $target2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('completed', $target1->fresh()->status);
        $this->assertEquals('completed', $target2->fresh()->status);
    }

    #[Test]
    public function teacher_can_bulk_destroy_targets(): void
    {
        $target1 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => 'active',
        ]);

        $target2 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 6,
            'ayah_end' => 10,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-targets.bulk-destroy'), [
            'target_ids' => [$target1->id, $target2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('hafalan_targets', ['id' => $target1->id]);
        $this->assertSoftDeleted('hafalan_targets', ['id' => $target2->id]);
    }
}
