<?php

namespace Tests\Unit\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class InternalNotificationServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private InternalNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->service = $this->app->make(InternalNotificationService::class);
    }

    #[Test]
    public function it_can_generate_notifications_for_all_active_users(): void
    {
        // Add an overdue target for the student
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => today()->subDays(2),
            'status' => 'active',
        ]);

        // Add a hafalan record needing attention
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'needs_improvement',
            'submitted_at' => now(),
        ]);

        // Add a murajaah record needing attention
        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'repeat',
            'reviewed_at' => now(),
        ]);

        // Run the command
        $created = $this->service->generateForAllActiveUsers();

        $this->assertTrue($created > 0);

        // Verify that only active users are processed
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        $notificationsForInactive = SystemNotification::where('user_id', $inactiveUser->id)->count();
        $this->assertEquals(0, $notificationsForInactive);

        // Check if admin, teacher, parent, and student got notifications
        $this->assertTrue(SystemNotification::where('user_id', $this->superAdmin->id)->exists());
        $this->assertTrue(SystemNotification::where('user_id', $this->teacherUser->id)->exists());
        $this->assertTrue(SystemNotification::where('user_id', $this->parentUser->id)->exists());
        $this->assertTrue(SystemNotification::where('user_id', $this->studentUser->id)->exists());
    }

    #[Test]
    public function it_persists_severity_and_source_for_generated_notifications(): void
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => today()->subDays(2),
            'status' => 'active',
        ]);

        $this->service->generateForAllActiveUsers();

        $notification = SystemNotification::where('user_id', $this->teacherUser->id)
            ->where('type', 'target_overdue')
            ->firstOrFail();

        $this->assertEquals('warning', $notification->severity);
        $this->assertEquals(HafalanTarget::class, $notification->source_type);
        $this->assertEquals($target->id, $notification->source_id);
        $this->assertEquals('bg-amber-100 text-amber-800', $notification->type_badge_class);
    }

    #[Test]
    public function it_does_not_create_duplicate_notifications(): void
    {
        // Add an overdue target
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'target_date' => today()->subDays(2),
            'status' => 'active',
        ]);

        // First run
        $firstRunCreated = $this->service->generateForAllActiveUsers();
        $this->assertTrue($firstRunCreated > 0);
        $totalNotifications = SystemNotification::count();

        // Second run
        $secondRunCreated = $this->service->generateForAllActiveUsers();

        // No new notifications should be created
        $this->assertEquals(0, $secondRunCreated);
        $this->assertEquals($totalNotifications, SystemNotification::count());
    }

    #[Test]
    public function it_ignores_completed_or_future_targets(): void
    {
        // Completed target
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'target_date' => today()->subDays(2),
            'status' => 'completed',
        ]);

        // Future target
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 4,
            'ayah_end' => 7,
            'target_date' => today()->addDays(2),
            'status' => 'active',
        ]);

        $created = $this->service->generateForAllActiveUsers();

        $this->assertEquals(0, $created);
        $this->assertEquals(0, SystemNotification::count());
    }
}
