<?php

namespace Tests\Unit\Services;

use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
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
    public function it_creates_parent_profile_if_not_exists_when_fetching_parent_stats(): void
    {
        $roleParent = Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orangtua']);
        $userWithoutProfile = User::factory()->create([
            'role_id' => $roleParent->id,
            'name' => 'Parent Without Profile',
        ]);

        $this->assertNull($userWithoutProfile->parentProfile);

        // This should create a new ParentProfile
        $stats = $this->service->parentStats($userWithoutProfile);

        // Verify a profile was created
        $this->assertNotNull($userWithoutProfile->parentProfile);
        $this->assertEquals($userWithoutProfile->id, $userWithoutProfile->parentProfile->user_id);

        // Verify the profile is also available in the returned stats array
        $this->assertArrayHasKey('parent', $stats);
        $this->assertEquals($userWithoutProfile->id, $stats['parent']->user_id);

        $this->assertDatabaseHas('parent_profiles', [
            'user_id' => $userWithoutProfile->id,
        ]);
    }

    #[Test]
    public function it_does_not_create_parent_profile_if_already_exists(): void
    {
        // $this->parentUser already has a parentProfile set up in SetsUpHafizPlusData
        $existingProfileId = $this->parentProfile->id;

        $this->assertNotNull($this->parentUser->parentProfile);
        $initialProfileCount = ParentProfile::count();

        $stats = $this->service->parentStats($this->parentUser);

        // Verify it returned the existing profile
        $this->assertArrayHasKey('parent', $stats);
        $this->assertEquals($existingProfileId, $stats['parent']->id);

        // Verify no new profiles were created
        $this->assertEquals($initialProfileCount, ParentProfile::count());
    }
}
