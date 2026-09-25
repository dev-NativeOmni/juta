<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 5-question average (q1..q5) is replaced by a single score the
     * teacher enters directly. `total_score` is kept as-is: historical
     * exams remain on their original 0-100 average-of-5 scale, while new
     * exams going forward are entered directly on the scale configured
     * in the tahfizh scoring settings (default 0-50). Historical values
     * are intentionally NOT rescaled to preserve academic record integrity.
     */
    public function up(): void
    {
        Schema::table('tahfizh_exams', function (Blueprint $table) {
            $table->dropColumn(['q1', 'q2', 'q3', 'q4', 'q5']);
        });
    }

    /**
     * Rollback cannot reconstruct the original q1-q5 values from
     * total_score alone; columns are restored as 0 (documented data loss).
     */
    public function down(): void
    {
        Schema::table('tahfizh_exams', function (Blueprint $table) {
            $table->integer('q1')->default(0);
            $table->integer('q2')->default(0);
            $table->integer('q3')->default(0);
            $table->integer('q4')->default(0);
            $table->integer('q5')->default(0);
        });
    }
};
