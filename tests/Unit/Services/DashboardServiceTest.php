<?php

namespace Tests\Unit\Services;

use App\Services\DashboardService;
use App\Services\StudentMotivationService;
use App\Services\StudentProgressService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Resolve from container to ensure proper injection of dependencies
        $this->service = $this->app->make(DashboardService::class);
    }

    #[Test]
    public function admin_stats_returns_empty_array_on_exception(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Simulated Cache error'));

        $result = $this->service->adminStats();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
