<?php

namespace Tests\Unit\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Services\HafalanTargetAutoCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;
use Carbon\Carbon;

class HafalanTargetAutoCompletionServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private HafalanTargetAutoCompletionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpHafizPlusData();

        $this->service = new HafalanTargetAutoCompletionService();
    }

    #[Test]
    public function completeTargetsFromRecord_does_not_process_if_record_is_not_passed(): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'repeat',
            'submitted_at' => now(),
        ]);

        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'active',
            'target_date' => now(),
        ]);

        $completedCount = $this->service->completeTargetsFromRecord($record);

        $this->assertEquals(0, $completedCount);
        $this->assertEquals('active', $target->fresh()->status);
    }

    #[Test]
    public function completeTargetsFromRecord_does_not_process_if_required_fields_are_missing(): void
    {
        $record = new HafalanRecord([
            'student_id' => $this->student->id,
            // 'surah_id' is missing
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
        ]);

        $completedCount = $this->service->completeTargetsFromRecord($record);

        $this->assertEquals(0, $completedCount);
    }

    #[Test]
    public function completeTargetsFromRecord_completes_matching_active_targets(): void
    {
        $targetDate = Carbon::now()->subDays(2);

        $target1 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'active',
            'target_date' => $targetDate,
        ]);

        $target2 = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 4,
            'ayah_end' => 5,
            'status' => 'active',
            'target_date' => $targetDate,
        ]);

        $outOfRangeTarget = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 6, // Exceeds record ayah_end (5)
            'status' => 'active',
            'target_date' => $targetDate,
        ]);

        $submittedAt = Carbon::now()->subDay();

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => $submittedAt,
        ]);

        $completedCount = $this->service->completeTargetsFromRecord($record);

        $this->assertEquals(2, $completedCount);

        $this->assertEquals('completed', $target1->fresh()->status);
        $this->assertEquals($submittedAt->copy()->endOfDay()->toDateTimeString(), $target1->fresh()->completed_at->toDateTimeString());

        $this->assertEquals('completed', $target2->fresh()->status);
        $this->assertEquals($submittedAt->copy()->endOfDay()->toDateTimeString(), $target2->fresh()->completed_at->toDateTimeString());

        $this->assertEquals('active', $outOfRangeTarget->fresh()->status);
        $this->assertNull($outOfRangeTarget->fresh()->completed_at);
    }

    #[Test]
    public function syncExistingTargets_updates_targets_and_returns_count(): void
    {
        $submittedAt = Carbon::now()->subDays(2);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => $submittedAt,
        ]);

        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'active',
            'target_date' => now(),
        ]);

        // This target shouldn't be matched because it's already completed
        $completedTarget = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'completed',
            'target_date' => now(),
            'completed_at' => now()->subDays(1),
        ]);

        $matchedCount = $this->service->syncExistingTargets(dryRun: false);

        $this->assertEquals(1, $matchedCount);
        $this->assertEquals('completed', $target->fresh()->status);
        $this->assertEquals($submittedAt->copy()->endOfDay()->toDateTimeString(), $target->fresh()->completed_at->toDateTimeString());
    }

    #[Test]
    public function syncExistingTargets_dry_run_does_not_update_targets_but_returns_count(): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => now()->subDays(2),
        ]);

        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'active',
            'target_date' => now(),
        ]);

        $matchedCount = $this->service->syncExistingTargets(dryRun: true);

        $this->assertEquals(1, $matchedCount);
        $this->assertEquals('active', $target->fresh()->status);
        $this->assertNull($target->fresh()->completed_at);
    }

    #[Test]
    public function matchingPassedRecordForTarget_finds_correct_record(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 2,
            'ayah_end' => 4,
            'status' => 'active',
            'target_date' => now(),
        ]);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5, // Covers 2-4
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $matchedRecord = $this->service->matchingPassedRecordForTarget($target);

        $this->assertNotNull($matchedRecord);
        $this->assertEquals($record->id, $matchedRecord->id);
    }

    #[Test]
    public function matchingPassedRecordForTarget_returns_earliest_record_when_multiple_match(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 2,
            'ayah_end' => 4,
            'status' => 'active',
            'target_date' => now(),
        ]);

        $laterRecord = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => now()->subDays(1),
        ]);

        $earlierRecord = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 2,
            'ayah_end' => 4,
            'status' => 'passed',
            'submitted_at' => now()->subDays(3),
        ]);

        $matchedRecord = $this->service->matchingPassedRecordForTarget($target);

        $this->assertNotNull($matchedRecord);
        $this->assertEquals($earlierRecord->id, $matchedRecord->id);
    }

    #[Test]
    public function matchingPassedRecordForTarget_returns_null_if_no_match(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 2,
            'ayah_end' => 4,
            'status' => 'active',
            'target_date' => now(),
        ]);

        // Wrong surah
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => \App\Models\Surah::create(['number' => 2, 'name_ar' => 'Test', 'name_latin' => 'Test', 'total_ayah' => 10, 'juz_start' => 1, 'juz_end' => 1])->id, // Different surah
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        // Wrong student
        HafalanRecord::create([
            'student_id' => \App\Models\Student::create(['user_id' => \App\Models\User::factory()->create()->id, 'class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Santri Test 2', 'student_number' => 'TEST-SNT-002', 'gender' => 'male', 'birth_date' => '2010-05-10', 'status' => 'active'])->id, // Different student
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        // Wrong status
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'status' => 'repeat', // Not passed
            'submitted_at' => now(),
        ]);

        // Out of range (does not fully cover target)
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 3,
            'ayah_end' => 4, // Missing ayah 2
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $matchedRecord = $this->service->matchingPassedRecordForTarget($target);

        $this->assertNull($matchedRecord);
    }
}
