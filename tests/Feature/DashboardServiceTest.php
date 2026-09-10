<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->dashboardService = $this->app->make(DashboardService::class);
    }

    public function test_it_caches_admin_stats(): void
    {
        $this->assertFalse(Cache::has('admin_dashboard_stats'));

        $this->dashboardService->adminStats();

        $this->assertTrue(Cache::has('admin_dashboard_stats'));
    }

    public function test_it_returns_empty_array_on_exception(): void
    {
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Simulated error'));

        $stats = $this->dashboardService->adminStats();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    public function test_it_returns_admin_stats_correctly(): void
    {
        $stats = $this->dashboardService->adminStats();

        $this->assertIsArray($stats);

        $this->assertArrayHasKey('total_students', $stats);
        $this->assertEquals(Student::query()->count(), $stats['total_students']);

        $this->assertArrayHasKey('active_students', $stats);
        $this->assertEquals(Student::query()->where('status', 'active')->count(), $stats['active_students']);

        $this->assertArrayHasKey('inactive_students', $stats);
        $this->assertEquals(Student::query()->where('status', 'inactive')->count(), $stats['inactive_students']);

        $this->assertArrayHasKey('graduated_students', $stats);
        $this->assertEquals(Student::query()->where('status', 'graduated')->count(), $stats['graduated_students']);

        $this->assertArrayHasKey('total_teachers', $stats);
        $this->assertEquals(TeacherProfile::query()->count(), $stats['total_teachers']);

        $this->assertArrayHasKey('total_parents', $stats);
        $this->assertEquals(ParentProfile::query()->count(), $stats['total_parents']);

        $this->assertArrayHasKey('total_programs', $stats);
        $this->assertEquals(Program::query()->count(), $stats['total_programs']);

        $this->assertArrayHasKey('total_class_rooms', $stats);
        $this->assertEquals(ClassRoom::query()->count(), $stats['total_class_rooms']);

        $this->assertArrayHasKey('hafalan_today', $stats);
        $this->assertArrayHasKey('murajaah_today', $stats);
        $this->assertArrayHasKey('active_targets', $stats);
        $this->assertArrayHasKey('overdue_targets', $stats);
        $this->assertArrayHasKey('completed_targets', $stats);
        $this->assertArrayHasKey('hafalan_need_attention', $stats);
        $this->assertArrayHasKey('murajaah_need_attention', $stats);

        $this->assertArrayHasKey('latest_hafalan_records', $stats);
        $this->assertArrayHasKey('latest_murajaah_records', $stats);
        $this->assertArrayHasKey('latest_targets', $stats);
        $this->assertArrayHasKey('students_progress', $stats);
    }
}
