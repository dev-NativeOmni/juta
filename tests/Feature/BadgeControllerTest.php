<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BadgeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'status' => 'active']);
        $this->teacher = User::factory()->create(['role_id' => $teacherRole->id, 'status' => 'active']);
    }

    private function validBadgePayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'first_hafalan',
            'title' => 'Setoran Pertama',
            'description' => 'Diberikan untuk setoran hafalan pertama.',
            'icon' => 'star',
            'type' => 'count_hafalan',
            'target_value' => 1,
            'sort_order' => 0,
        ], $overrides);
    }

    #[Test]
    public function guest_is_redirected_from_badges_index(): void
    {
        $this->get(route('badges.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_privileged_role_cannot_manage_badges(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('badges.index'))
            ->assertStatus(403);

        $this->actingAs($this->teacher)
            ->post(route('badges.store'), $this->validBadgePayload())
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_badges_index(): void
    {
        Badge::create($this->validBadgePayload() + ['is_active' => true]);

        $this->actingAs($this->admin)
            ->get(route('badges.index'))
            ->assertStatus(200)
            ->assertSee('Setoran Pertama');
    }

    #[Test]
    public function admin_can_create_a_badge(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('badges.store'), $this->validBadgePayload());

        $response->assertRedirect(route('badges.index'));

        $this->assertDatabaseHas('badges', [
            'key' => 'first_hafalan',
            'title' => 'Setoran Pertama',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_cannot_create_badge_with_duplicate_key(): void
    {
        Badge::create($this->validBadgePayload() + ['is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('badges.store'), $this->validBadgePayload(['title' => 'Duplikat']))
            ->assertSessionHasErrors('key');
    }

    #[Test]
    public function admin_can_update_a_badge(): void
    {
        $badge = Badge::create($this->validBadgePayload() + ['is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('badges.update', $badge), $this->validBadgePayload([
            'title' => 'Setoran Pertama (Diperbarui)',
            'target_value' => 3,
        ]));

        $response->assertRedirect(route('badges.index'));

        $this->assertDatabaseHas('badges', [
            'id' => $badge->id,
            'title' => 'Setoran Pertama (Diperbarui)',
            'target_value' => 3,
        ]);
    }

    #[Test]
    public function admin_can_toggle_badge_active_status(): void
    {
        $badge = Badge::create($this->validBadgePayload() + ['is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('badges.toggle', $badge))
            ->assertRedirect(route('badges.index'));

        $this->assertFalse($badge->fresh()->is_active);
    }

    #[Test]
    public function admin_can_delete_a_badge(): void
    {
        $badge = Badge::create($this->validBadgePayload() + ['is_active' => true]);

        $this->actingAs($this->admin)
            ->delete(route('badges.destroy', $badge))
            ->assertRedirect(route('badges.index'));

        $this->assertDatabaseMissing('badges', ['id' => $badge->id]);
    }
}
