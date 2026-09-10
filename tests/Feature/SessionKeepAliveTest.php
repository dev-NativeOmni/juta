<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class SessionKeepAliveTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_keep_alive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('keep-alive'));

        $response->assertOk()
            ->assertJsonStructure(['status', 'csrf'])
            ->assertJson(['status' => 'ok']);
    }

    public function test_unauthenticated_user_cannot_access_keep_alive(): void
    {
        $response = $this->getJson(route('keep-alive'));

        $response->assertUnauthorized();
    }

    public function test_json_request_on_token_mismatch_returns_419_json(): void
    {
        $user = User::factory()->create();

        $exception = new TokenMismatchException('CSRF token mismatch.');
        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $exception);

        $this->assertEquals(419, $response->getStatusCode());
    }
}
