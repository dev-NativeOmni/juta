<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        putenv('SUPERADMIN_USERNAME');
        putenv('SUPERADMIN_PASSWORD');
        parent::tearDown();
    }

    public function test_it_creates_super_admin_by_username_with_generated_password(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $user = User::where('username', 'superadmin')->firstOrFail();
        $this->assertSame('super_admin', $user->role->name);
        $this->assertSame('active', $user->status);
        $this->assertFalse(Hash::check('password123', $user->password));
    }

    public function test_it_uses_credentials_from_env(): void
    {
        putenv('SUPERADMIN_USERNAME=kepala_it');
        putenv('SUPERADMIN_PASSWORD=RahasiaSekali123');

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::where('username', 'kepala_it')->count());
        $user = User::where('username', 'kepala_it')->firstOrFail();
        $this->assertTrue(Hash::check('RahasiaSekali123', $user->password));
    }
}
