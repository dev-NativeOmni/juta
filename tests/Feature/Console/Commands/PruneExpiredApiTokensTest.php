<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PruneExpiredApiTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_if_table_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('personal_access_tokens')
            ->andReturn(false);

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Table personal_access_tokens tidak ditemukan.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_it_fails_if_expires_at_column_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('personal_access_tokens')
            ->andReturn(true);

        Schema::shouldReceive('hasColumn')
            ->with('personal_access_tokens', 'expires_at')
            ->andReturn(false);

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Column expires_at belum ada di personal_access_tokens.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_it_can_dry_run_pruning(): void
    {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => 1,
            'name' => 'test_token',
            'token' => 'some_hashed_token',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(1),
        ]);

        $this->artisan('ims:prune-api-tokens', ['--dry-run' => true])
            ->expectsOutput('Dry run: 1 expired API token(s) would be deleted.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'test_token',
        ]);
    }

    public function test_it_can_prune_expired_tokens(): void
    {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => 1,
            'name' => 'expired_token',
            'token' => 'some_hashed_token_1',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(1),
        ]);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => 1,
            'name' => 'valid_token',
            'token' => 'some_hashed_token_2',
            'abilities' => '["*"]',
            'expires_at' => now()->addDays(1),
        ]);

        $this->artisan('ims:prune-api-tokens')
            ->expectsOutput('Deleted 1 expired API token(s).')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'expired_token',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'valid_token',
        ]);
    }

    public function test_it_can_prune_expired_tokens_with_days_option(): void
    {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => 1,
            'name' => 'very_expired_token',
            'token' => 'some_hashed_token_1',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(10),
        ]);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => 1,
            'name' => 'recently_expired_token',
            'token' => 'some_hashed_token_2',
            'abilities' => '["*"]',
            'expires_at' => now()->subDays(2),
        ]);

        $this->artisan('ims:prune-api-tokens', ['--days' => 7])
            ->expectsOutput('Deleted 1 expired API token(s).')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'very_expired_token',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'recently_expired_token',
        ]);
    }
}
