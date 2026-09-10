<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\StudentMotivationService;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->service = $this->app->make(DashboardService::class);
    }

    #[Test]
    public function it_creates_student_profile_if_missing_when_fetching_stats(): void
    {
        // Create a user without a student profile
        $user = User::factory()->create([
            'name' => 'New Student User',
            'status' => 'active',
        ]);

        $this->assertNull($user->studentProfile);
        $this->assertDatabaseMissing('students', [
            'user_id' => $user->id,
        ]);

        $stats = $this->service->studentStats($user);

        $this->assertNotNull($stats['student']);
        $this->assertInstanceOf(Student::class, $stats['student']);
        $this->assertEquals($user->id, $stats['student']->user_id);
        $this->assertEquals($user->name, $stats['student']->name);
        $this->assertEquals('active', $stats['student']->status);

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'name' => $user->name,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_fetches_stats_for_user_with_existing_student_profile(): void
    {
        // Use the studentUser created by the SetsUpHafizPlusData trait
        $user = clone $this->studentUser;
        $user->load('studentProfile');

        $this->assertNotNull($user->studentProfile);

        $stats = $this->service->studentStats($user);

        $this->assertArrayHasKey('student', $stats);
        $this->assertEquals($this->student->id, $stats['student']->id);

        $this->assertArrayHasKey('progress', $stats);
        $this->assertIsArray($stats['progress']);

        $this->assertArrayHasKey('summary', $stats);
        $this->assertIsArray($stats['summary']);

        $this->assertArrayHasKey('motivation', $stats);

        $this->assertArrayHasKey('active_targets', $stats);
        $this->assertArrayHasKey('overdue_targets', $stats);
        $this->assertArrayHasKey('latest_targets', $stats);
        $this->assertArrayHasKey('latest_hafalan_records', $stats);
        $this->assertArrayHasKey('latest_murajaah_records', $stats);
    }
}
