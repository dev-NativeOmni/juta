<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneExpiredApiTokens extends Command
{
    protected $signature = 'tad:prune-api-tokens
                            {--days=0 : Delete tokens expired at least this many days ago}
                            {--dry-run : Show how many tokens would be deleted without deleting}';

    protected $aliases = ['ims:prune-api-tokens'];

    protected $description = 'Delete expired Sanctum personal access tokens for TAD API.';

    public function handle(): int
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            $this->error('Table personal_access_tokens tidak ditemukan.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            $this->error('Column expires_at belum ada di personal_access_tokens.');

            return self::FAILURE;
        }

        $days = max(0, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = DB::table('personal_access_tokens')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $cutoff);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} expired API token(s) would be deleted.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Deleted {$deleted} expired API token(s).");

        return self::SUCCESS;
    }
}
