<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen plain_password to TEXT (encrypted payloads are longer than the
     * original VARCHAR(255)) and encrypt any value still stored as plaintext.
     *
     * Uses raw DDL instead of Schema::table()->change() because
     * doctrine/dbal is not installed in this project.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'plain_password')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE users MODIFY plain_password TEXT NULL'),
            'pgsql' => DB::statement('ALTER TABLE users ALTER COLUMN plain_password TYPE TEXT'),
            default => null, // sqlite has no real column length limit
        };

        DB::table('users')
            ->whereNotNull('plain_password')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    if ($this->isAlreadyEncrypted($user->plain_password)) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['plain_password' => Crypt::encryptString($user->plain_password)]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'plain_password')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE users MODIFY plain_password VARCHAR(255) NULL'),
            'pgsql' => DB::statement('ALTER TABLE users ALTER COLUMN plain_password TYPE VARCHAR(255)'),
            default => null,
        };
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
