<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstitutionGateTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'code' => 'SMAIT01',
            'name' => 'SMAIT Al-Hikmah Solo',
            'slug' => 'smait-al-hikmah-solo',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function user_can_view_portal_gate_screen(): void
    {
        $response = $this->get(route('portal.gate'));

        $response->assertStatus(200);
        $response->assertSee('Gerbang Akses Lembaga');
        $response->assertSee('Kode Lembaga / Sekolah');
    }

    #[Test]
    public function user_can_verify_valid_institution_code(): void
    {
        $response = $this->post(route('portal.verify'), [
            'school_code' => 'SMAIT01',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertEquals($this->institution->id, session(InstitutionContext::SESSION_KEY));
        $this->assertEquals($this->institution->id, app(InstitutionContext::class)->id());
    }

    #[Test]
    public function user_can_verify_valid_institution_code_in_lowercase(): void
    {
        $response = $this->post(route('portal.verify'), [
            'school_code' => 'smait01',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertEquals($this->institution->id, session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function user_cannot_verify_non_existent_institution_code(): void
    {
        $response = $this->from(route('portal.gate'))->post(route('portal.verify'), [
            'school_code' => 'INVALIDCODE999',
        ]);

        $response->assertRedirect(route('portal.gate'));
        $response->assertSessionHasErrors('school_code');
        $this->assertNull(session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function user_cannot_verify_inactive_institution(): void
    {
        $inactive = Institution::create([
            'code' => 'INACTIVE01',
            'name' => 'Sekolah Nonaktif',
            'is_active' => false,
        ]);

        $response = $this->from(route('portal.gate'))->post(route('portal.verify'), [
            'school_code' => 'INACTIVE01',
        ]);

        $response->assertRedirect(route('portal.gate'));
        $response->assertSessionHasErrors('school_code');
        $this->assertNull(session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function user_can_access_via_direct_url(): void
    {
        $response = $this->get(route('portal.direct', ['code' => 'SMAIT01']));

        $response->assertRedirect(route('login'));
        $this->assertEquals($this->institution->id, session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function user_can_access_via_slug_direct_url(): void
    {
        $response = $this->get('/portal/smait-al-hikmah-solo');

        $response->assertRedirect(route('login'));
        $this->assertEquals($this->institution->id, session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function invalid_direct_url_redirects_to_gate_with_error(): void
    {
        $response = $this->get(route('portal.direct', ['code' => 'BOGUS']));

        $response->assertRedirect(route('portal.gate'));
        $response->assertSessionHasErrors('school_code');
    }

    #[Test]
    public function user_can_exit_and_clear_institution_session(): void
    {
        session([InstitutionContext::SESSION_KEY => $this->institution->id]);

        $response = $this->post(route('portal.exit'));

        $response->assertRedirect(route('portal.gate'));
        $this->assertNull(session(InstitutionContext::SESSION_KEY));
    }

    #[Test]
    public function login_page_displays_active_institution_branding(): void
    {
        session([InstitutionContext::SESSION_KEY => $this->institution->id]);

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('SMAIT Al-Hikmah Solo');
        $response->assertSee('SMAIT01');
        $response->assertSee(route('portal.exit'));
    }
}
