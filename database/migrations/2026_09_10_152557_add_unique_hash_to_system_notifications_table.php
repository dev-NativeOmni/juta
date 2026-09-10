<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('system_notifications', 'unique_hash')) {
                $table->string('unique_hash')->nullable()->after('created_by')->unique();
            }
            if (!Schema::hasColumn('system_notifications', 'severity')) {
                $table->string('severity')->nullable()->after('type');
            }
            if (!Schema::hasColumn('system_notifications', 'source_type')) {
                $table->string('source_type')->nullable()->after('message');
            }
            if (!Schema::hasColumn('system_notifications', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            $table->dropColumn(['unique_hash', 'severity', 'source_type', 'source_id']);
        });
    }
};
