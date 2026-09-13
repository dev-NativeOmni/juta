<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dashboard queries filter student_points by (type, date) and
     * adab_records by assessment_date on nearly every page load, but
     * neither column was ever indexed (only the student_id/logged_by
     * foreign keys were). Follows the same guarded pattern as
     * 2026_08_18_000000_add_database_performance_indexes.php.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_points')) {
            try {
                Schema::table('student_points', function (Blueprint $table) {
                    $table->index(['type', 'date'], 'idx_student_points_type_date');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }

        if (Schema::hasTable('adab_records')) {
            try {
                Schema::table('adab_records', function (Blueprint $table) {
                    $table->index('assessment_date', 'idx_adab_records_assessment_date');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_points')) {
            try {
                Schema::table('student_points', function (Blueprint $table) {
                    $table->dropIndex('idx_student_points_type_date');
                });
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasTable('adab_records')) {
            try {
                Schema::table('adab_records', function (Blueprint $table) {
                    $table->dropIndex('idx_adab_records_assessment_date');
                });
            } catch (Throwable $e) {
            }
        }
    }
};
