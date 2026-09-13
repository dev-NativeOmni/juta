<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enforcement is off by default in the testing environment (see
        // .env.testing) so the rest of the suite doesn't need every
        // super-admin fixture to enroll in 2FA. This test explicitly turns
        // it back on to verify the feature itself.
        config(['hafizplus.two_factor.enforce' => true]);
    }

    private function currentOtpFor(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    #[Test]
    public function super_admin_without_two_factor_is_redirected_to_setup(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $superAdmin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('two-factor.show'));
    }

    #[Test]
    public function non_required_role_is_not_forced_into_two_factor_setup(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function user_can_enroll_in_two_factor_authentication(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $superAdmin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        // Setup page itself must stay reachable even though the user isn't enrolled yet.
        $this->actingAs($superAdmin)
            ->get(route('two-factor.show'))
            ->assertStatus(200);

        $this->actingAs($superAdmin)
            ->post(route('two-factor.enable'))
            ->assertRedirect(route('two-factor.show'));

        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $response = $this->actingAs($superAdmin)->post(route('two-factor.confirm'), [
            'code' => $this->currentOtpFor($secret),
        ]);

        $response->assertRedirect(route('two-factor.show'));

        $superAdmin->refresh();
        $this->assertTrue($superAdmin->hasEnabledTwoFactorAuthentication());
        $this->assertEquals($secret, $superAdmin->two_factor_secret);
        $this->assertCount(8, $superAdmin->two_factor_recovery_codes);

        // Now enrolled, so the enrollment gate no longer blocks them.
        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('super-admin.dashboard'));
    }

    #[Test]
    public function confirming_with_invalid_code_does_not_enable_two_factor(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $superAdmin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($superAdmin)->post(route('two-factor.enable'));

        $response = $this->actingAs($superAdmin)->post(route('two-factor.confirm'), [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($superAdmin->fresh()->hasEnabledTwoFactorAuthentication());
    }

    #[Test]
    public function login_challenges_user_with_two_factor_enabled(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $secret = (new Google2FA)->generateSecretKey();

        $superAdmin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'username' => 'super2fa',
            'password' => 'password123',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAA-1111', 'BBBB-2222'],
            'two_factor_confirmed_at' => now(),
        ]);

        // Correct password alone must NOT complete login.
        $response = $this->post(route('login'), [
            'username' => 'super2fa',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertEquals($superAdmin->id, session('two_factor.user_id'));

        // Wrong OTP code is rejected.
        $this->post(route('two-factor.challenge.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // Correct OTP code completes login.
        $this->post(route('two-factor.challenge.store'), [
            'code' => $this->currentOtpFor($secret),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($superAdmin);
    }

    #[Test]
    public function recovery_code_can_be_used_once_to_complete_login(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $secret = (new Google2FA)->generateSecretKey();

        $superAdmin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'username' => 'super2fa',
            'password' => 'password123',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAA-1111', 'BBBB-2222'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post(route('login'), [
            'username' => 'super2fa',
            'password' => 'password123',
        ]);

        $this->post(route('two-factor.challenge.store'), ['code' => 'aaaa-1111'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($superAdmin);

        $superAdmin->refresh();
        $this->assertCount(1, $superAdmin->two_factor_recovery_codes);
        $this->assertNotContains('AAAA-1111', $superAdmin->two_factor_recovery_codes);

        // The same recovery code cannot be reused.
        auth()->logout();
        $this->post(route('login'), [
            'username' => 'super2fa',
            'password' => 'password123',
        ]);

        $this->post(route('two-factor.challenge.store'), ['code' => 'AAAA-1111'])
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function super_admin_cannot_disable_required_two_factor(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $secret = (new Google2FA)->generateSecretKey();

        $superAdmin = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'password' => 'password123',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAA-1111'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('two-factor.disable'), ['password' => 'password123'])
            ->assertStatus(403);

        $this->assertTrue($superAdmin->fresh()->hasEnabledTwoFactorAuthentication());
    }
}
