<?php

namespace Tests\Feature\Api\V1;

use App\Models\Surah;
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

    public function test_surah_lookup_uses_surah_number_not_database_id(): void
    {
        // Deliberately push this surah's auto-increment id far away from its
        // Quran number, to guard against a regression where the controller
        // looked up by primary key instead of the `number` column — that
        // bug was invisible whenever id happened to equal number (e.g. on a
        // freshly seeded database) and only surfaced once ids drifted, as
        // they do under Postgres where sequences aren't reset by
        // RefreshDatabase's transaction rollback between tests.
        Surah::query()->where('number', 2)->delete();

        $surah = Surah::create([
            'number' => 2,
            'name_ar' => 'البقرة',
            'name_latin' => 'Al-Baqarah (Test)',
            'total_ayah' => 286,
            'juz_start' => 1,
            'juz_end' => 3,
        ]);

        $this->assertNotEquals(2, $surah->id, 'Test setup needs the id to differ from the surah number.');

        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/surahs/2')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['surah' => ['number' => 2, 'name_latin' => 'Al-Baqarah (Test)']],
            ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/surahs/2/ayahs')
            ->assertStatus(200)
            ->assertJsonPath('data.surah.number', 2);
    }
}
