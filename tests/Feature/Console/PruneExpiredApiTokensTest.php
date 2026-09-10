<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PruneExpiredApiTokensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the tokens table exists for standard tests
        if (! Schema::hasTable('personal_access_tokens')) {
            $this->artisan('migrate');
        }
    }

    public function test_it_deletes_expired_tokens()
    {
        // Insert active token
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 1,
            'name' => 'Test Token 1',
            'token' => 'token_hash_1',
            'abilities' => '["*"]',
            'expires_at' => now()->addDays(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert expired token
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 2,
            'name' => 'Test Token 2',
            'token' => 'token_hash_2',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(2, DB::table('personal_access_tokens')->count());

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Deleted 1 expired API token(s).')
            ->assertSuccessful();

        $this->assertEquals(1, DB::table('personal_access_tokens')->count());
        $this->assertEquals('Test Token 1', DB::table('personal_access_tokens')->first()->name);
    }

    public function test_it_does_not_delete_tokens_in_dry_run_mode()
    {
        // Insert expired token
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 1,
            'name' => 'Test Token',
            'token' => 'token_hash',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(1, DB::table('personal_access_tokens')->count());

        $this->artisan('ims:prune-api-tokens', ['--dry-run' => true])
            ->expectsOutput('Dry run: 1 expired API token(s) would be deleted.')
            ->assertSuccessful();

        // Ensure token still exists
        $this->assertEquals(1, DB::table('personal_access_tokens')->count());
    }

    public function test_it_respects_the_days_option()
    {
        // Token expired 2 days ago
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 1,
            'name' => 'Token 2 Days Ago',
            'token' => 'hash_1',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Token expired 10 days ago
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 2,
            'name' => 'Token 10 Days Ago',
            'token' => 'hash_2',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(2, DB::table('personal_access_tokens')->count());

        // Delete tokens expired at least 5 days ago
        $this->artisan('ims:prune-api-tokens', ['--days' => 5])
            ->expectsOutput('Deleted 1 expired API token(s).')
            ->assertSuccessful();

        // The token expired 2 days ago should still be there
        $this->assertEquals(1, DB::table('personal_access_tokens')->count());
        $this->assertEquals('Token 2 Days Ago', DB::table('personal_access_tokens')->first()->name);
    }

    public function test_it_fails_if_table_does_not_exist()
    {
        Schema::dropIfExists('personal_access_tokens');

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Table personal_access_tokens tidak ditemukan.')
            ->assertFailed();
    }

    public function test_it_fails_if_expires_at_column_does_not_exist()
    {
        // Recreate the table without the expires_at column
        Schema::dropIfExists('personal_access_tokens');
        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->string('token');
        });

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Column expires_at belum ada di personal_access_tokens.')
            ->assertFailed();
    }
}
