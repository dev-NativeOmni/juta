<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Menu "Badge" (Badges Management) dibatasi khusus Super Admin -- sebelumnya
 * admin & coordinator_tahfizh juga punya akses, dan link menunya tampil untuk
 * semua role yang login (cuma dicek Route::has(), bukan role) meski hanya
 * 3 role yang benar-benar bisa membukanya tanpa kena 403.
 */
class BadgeAccessTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function only_super_admin_can_view_the_badges_page(): void
    {
        $this->actingAs($this->superAdmin)->get(route('badges.index'))->assertOk();

        $this->actingAs($this->admin)->get(route('badges.index'))->assertForbidden();

        $coordinatorRole = Role::firstOrCreate(['name' => 'coordinator_tahfizh'], ['display_name' => 'Koordinator Tahfizh']);
        $coordinator = User::factory()->create(['role_id' => $coordinatorRole->id, 'status' => 'active']);
        $this->actingAs($coordinator)->get(route('badges.index'))->assertForbidden();

        $this->actingAs($this->teacherUser)->get(route('badges.index'))->assertForbidden();
    }

    #[Test]
    public function badge_menu_link_only_renders_for_super_admin(): void
    {
        $superAdminDashboard = $this->actingAs($this->superAdmin)->get(route('super-admin.dashboard'));
        $superAdminDashboard->assertSee('>Badge<', false);

        $adminDashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminDashboard->assertDontSee('>Badge<', false);
    }
}
