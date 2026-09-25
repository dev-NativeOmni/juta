<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Setting;
use App\Models\TahfizhExam;
use App\Services\HafalanTargetAutoCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class TahfizhScoringTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function createHafalanRecord(array $overrides = []): HafalanRecordSurah
    {
        $record = HafalanRecord::create([
            'student_id' => $overrides['student_id'] ?? $this->student->id,
            'teacher_id' => $overrides['teacher_id'] ?? $this->teacherProfile->id,
            'submitted_at' => $overrides['submitted_at'] ?? now(),
        ]);

        return $record->surahs()->create([
            'surah_id' => $overrides['surah_id'] ?? $this->surah->id,
            'ayah_start' => $overrides['ayah_start'] ?? 1,
            'ayah_end' => $overrides['ayah_end'] ?? 7,
            'submission_type' => $overrides['submission_type'] ?? 'new',
            'status' => $overrides['status'] ?? 'passed',
            'score' => $overrides['score'] ?? null,
        ]);
    }

    // =========================================================================
    // Setting::calculateTahfizhScore()
    // =========================================================================

    #[Test]
    public function final_score_sums_target_and_exam_when_target_completed(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 7,
            'target_date' => now(),
            'status' => 'completed',
        ]);

        TahfizhExam::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'total_score' => 45,
            'exam_date' => now(),
        ]);

        $result = Setting::calculateTahfizhScore($this->student->fresh());

        $this->assertSame(50.0, (float) $result['target_score']);
        $this->assertSame(45.0, (float) $result['exam_score']);
        $this->assertSame(95.0, (float) $result['final_score']);
        $this->assertSame('Tuntas', $result['target_label']);
    }

    #[Test]
    public function target_not_completed_still_earns_default_points(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 7,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $result = Setting::calculateTahfizhScore($this->student->fresh());

        $this->assertSame(40.0, (float) $result['target_score']);
        $this->assertSame('Belum Tuntas', $result['target_label']);
    }

    #[Test]
    public function score_is_zero_components_when_student_has_no_target_or_exam(): void
    {
        $result = Setting::calculateTahfizhScore($this->student->fresh());

        $this->assertNull($result['target_score']);
        $this->assertNull($result['exam_score']);
        $this->assertSame(0.0, (float) $result['final_score']);
        $this->assertFalse($result['has_target']);
        $this->assertFalse($result['has_exam']);
    }

    #[Test]
    public function legacy_exam_score_above_exam_weight_is_capped(): void
    {
        // Skor lama hasil rata-rata 5 soal (skala 0-100), sebelum penyederhanaan.
        TahfizhExam::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'total_score' => 88,
            'exam_date' => now(),
        ]);

        $result = Setting::calculateTahfizhScore($this->student->fresh());

        $this->assertSame(50.0, (float) $result['exam_score']);
    }

    #[Test]
    public function only_latest_target_and_exam_are_used(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 7,
            'target_date' => now()->subDays(10),
            'status' => 'completed',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $result = Setting::calculateTahfizhScore($this->student->fresh());

        // Target terbaru (belum tuntas) yang dipakai, bukan yang lama (tuntas).
        $this->assertSame(40.0, (float) $result['target_score']);
    }

    // =========================================================================
    // Pengaturan bobot (settings page)
    // =========================================================================

    #[Test]
    public function super_admin_and_admin_can_access_tahfizh_scoring_settings(): void
    {
        $this->actingAs($this->superAdmin)->get(route('settings.tahfizh-scoring'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('settings.tahfizh-scoring'))->assertStatus(200);
    }

    #[Test]
    public function teacher_cannot_access_tahfizh_scoring_settings(): void
    {
        $this->actingAs($this->teacherUser)->get(route('settings.tahfizh-scoring'))->assertStatus(403);
    }

    #[Test]
    public function weights_must_sum_to_100(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.tahfizh-scoring.update'), [
            'target_weight' => 60,
            'exam_weight' => 60,
            'target_incomplete_score' => 40,
        ]);

        $response->assertSessionHasErrors('target_weight');
    }

    #[Test]
    public function incomplete_score_cannot_exceed_target_weight(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.tahfizh-scoring.update'), [
            'target_weight' => 50,
            'exam_weight' => 50,
            'target_incomplete_score' => 60,
        ]);

        $response->assertSessionHasErrors('target_incomplete_score');
    }

    #[Test]
    public function admin_can_update_and_reset_scoring_weights(): void
    {
        $this->actingAs($this->admin)->post(route('settings.tahfizh-scoring.update'), [
            'target_weight' => 60,
            'exam_weight' => 40,
            'target_incomplete_score' => 30,
        ])->assertSessionHas('success');

        $this->assertSame(60, Setting::getTahfizhScoringConfig()['target_weight']);

        $this->actingAs($this->admin)->post(route('settings.tahfizh-scoring.reset'))
            ->assertSessionHas('success');

        $this->assertSame(50, Setting::getTahfizhScoringConfig()['target_weight']);
    }

    // =========================================================================
    // Render halaman form (single-score / single-ayah field)
    // =========================================================================

    #[Test]
    public function tahfizh_exam_create_and_edit_pages_render_with_single_score_field(): void
    {
        $this->actingAs($this->admin)->get(route('tahfizh-exams.create'))->assertStatus(200);

        $exam = TahfizhExam::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'total_score' => 42,
            'exam_date' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('tahfizh-exams.edit', $exam))->assertStatus(200);
    }

    #[Test]
    public function hafalan_target_create_and_edit_pages_render_with_single_ayah_field(): void
    {
        $this->actingAs($this->admin)->get(route('hafalan-targets.create'))->assertStatus(200);

        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->get(route('hafalan-targets.edit', $target))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('hafalan-targets.show', $target))->assertStatus(200);
    }

    #[Test]
    public function tahfizh_exam_can_be_stored_with_single_score_field(): void
    {
        $response = $this->actingAs($this->admin)->post(route('tahfizh-exams.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'type' => 'surah',
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'score' => 47,
            'exam_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('tahfizh-exams.index'));
        $this->assertDatabaseHas('tahfizh_exams', [
            'student_id' => $this->student->id,
            'total_score' => 47,
        ]);
    }

    // =========================================================================
    // Auto-completion matching (single ayah milestone semantics)
    // =========================================================================

    #[Test]
    public function target_matches_a_record_that_does_not_start_at_ayah_one(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => now(),
            'status' => 'active',
        ]);

        // Setoran murid untuk sesi ini mencakup ayat 3-7 (tidak mulai dari ayat 1),
        // tapi tetap mencakup sampai ayat target (5).
        $record = $this->createHafalanRecord([
            'ayah_start' => 3,
            'ayah_end' => 7,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $service = app(HafalanTargetAutoCompletionService::class);
        $matched = $service->matchingPassedRecordForTarget($target);

        $this->assertNotNull($matched);
        $this->assertSame($record->id, $matched->id);
    }

    #[Test]
    public function target_does_not_match_a_record_that_falls_short_of_the_target_ayah(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 7,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $this->createHafalanRecord([
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $service = app(HafalanTargetAutoCompletionService::class);

        $this->assertNull($service->matchingPassedRecordForTarget($target));
    }
}
