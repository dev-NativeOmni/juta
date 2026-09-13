<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * InternalNotificationService::createUniqueNotification() has always
     * written `severity`, `source_type` and `source_id`, but neither column
     * ever existed, so Eloquent silently dropped them on every insert.
     */
    public function up(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        Schema::table('system_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('system_notifications', 'severity')) {
                $table->string('severity')->nullable()->after('type');
            }

            if (! Schema::hasColumn('system_notifications', 'source_type')) {
                $table->nullableMorphs('source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        Schema::table('system_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('system_notifications', 'source_type')) {
                $table->dropMorphs('source');
            }

            if (Schema::hasColumn('system_notifications', 'severity')) {
                $table->dropColumn('severity');
            }
        });
    }
};
