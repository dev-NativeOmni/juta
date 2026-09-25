<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_settings_route_requires_authentication(): void
    {
        $response = $this->get('/settings');
        $response->assertRedirect('/login');
    }

    public function test_settings_route_can_be_accessed_by_super_admin(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->get('/settings');
        $response->assertStatus(200);
    }

    public function test_settings_route_cannot_be_accessed_by_teacher_or_student(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $student = User::where('username', 'santri')->first();

        $responseTeacher = $this->actingAs($teacher)->get('/settings');
        $responseTeacher->assertStatus(403);

        $responseStudent = $this->actingAs($student)->get('/settings');
        $responseStudent->assertStatus(403);
    }

    public function test_super_admin_can_update_settings_text_only(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post('/settings', [
            'nama_instansi' => 'Pondok Pesantren Al-Huda',
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasNoErrors();

        $this->assertEquals('Pondok Pesantren Al-Huda', Setting::get('nama_instansi'));
    }

    public function test_super_admin_can_upload_logo_and_background(): void
    {
        Storage::fake('public');
        $superAdmin = User::where('username', 'superadmin')->first();

        $logoFile = UploadedFile::fake()->create('custom_logo.png', 100, 'image/png');
        $bgFile = UploadedFile::fake()->create('custom_bg.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($superAdmin)->post('/settings', [
            'nama_instansi' => 'PP Al-Huda',
            'logo' => $logoFile,
            'login_bg' => $bgFile,
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasNoErrors();

        $storedLogo = Setting::get('logo');
        $storedBg = Setting::get('login_bg');

        $this->assertNotNull($storedLogo);
        $this->assertNotNull($storedBg);

        Storage::disk('public')->assertExists($storedLogo);
        Storage::disk('public')->assertExists($storedBg);
    }

    public function test_super_admin_can_reset_logo_and_background(): void
    {
        Storage::fake('public');

        Setting::set('logo', 'settings/old_logo.png');
        Setting::set('login_bg', 'settings/old_bg.jpg');

        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post('/settings', [
            'reset_logo' => '1',
            'reset_login_bg' => '1',
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasNoErrors();

        $this->assertNull(Setting::get('logo'));
        $this->assertNull(Setting::get('login_bg'));
    }
}
