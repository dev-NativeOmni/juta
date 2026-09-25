<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\QuranDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            QuranDataSeeder::class,
        ]);
    }

    public function test_user_can_list_surahs(): void
    {
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/surahs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'surahs',
                ],
                'status_code',
                'meta' => [
                    'pagination',
                ],
            ]);

        $this->assertNotEmpty($response['data']['surahs']);
    }

    public function test_user_can_get_surah_detail(): void
    {
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/surahs/1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'surah' => [
                        'number' => 1,
                        'name_latin' => 'Al-Fatihah',
                    ],
                ],
            ]);
    }

    public function test_user_can_get_ayahs_of_surah(): void
    {
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/surahs/1/ayahs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'surah',
                    'ayahs',
                ],
                'status_code',
            ]);

        $this->assertCount(7, $response['data']['ayahs']);
    }
}
