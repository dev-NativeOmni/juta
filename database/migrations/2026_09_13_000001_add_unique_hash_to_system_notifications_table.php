<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SystemNotification::booted() and InternalNotificationService both
     * read/write `unique_hash` (used to deduplicate generated notifications),
     * but no prior migration ever added the column.
     */
    public function up(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        if (! Schema::hasColumn('system_notifications', 'unique_hash')) {
            Schema::table('system_notifications', function (Blueprint $table) {
                $table->string('unique_hash')
                    ->nullable()
                    ->unique()
                    ->after('created_by');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        if (Schema::hasColumn('system_notifications', 'unique_hash')) {
            Schema::table('system_notifications', function (Blueprint $table) {
                $table->dropUnique(['unique_hash']);
                $table->dropColumn('unique_hash');
            });
        }
    }
};
