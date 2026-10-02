<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * List of tables to equip with institution_id tenancy scoping.
     */
    protected array $tables = [
        'users',
        'programs',
        'class_rooms',
        'teacher_profiles',
        'parent_profiles',
        'students',
        'hafalan_records',
        'murajaah_records',
        'hafalan_targets',
        'tahfizh_exams',
        'attendances',
        'student_points',
        'student_reports',
        'adab_records',
        'adab_mentor_assessments',
        'calendar_days',
        'calendar_month_locks',
        'class_week_schedules',
        'badges',
        'internal_notifications',
        'audit_logs',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultInstitutionId = Schema::hasTable('institutions')
            ? DB::table('institutions')->value('id')
            : null;

        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'institution_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('institution_id')->nullable()->index()->after('id');
                });

                if ($defaultInstitutionId) {
                    DB::table($tableName)
                        ->whereNull('institution_id')
                        ->update(['institution_id' => $defaultInstitutionId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'institution_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('institution_id');
                });
            }
        }
    }
};
