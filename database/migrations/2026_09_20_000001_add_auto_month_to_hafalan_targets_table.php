<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda target yang dibuat otomatis oleh sistem (per murid per bulan, format
     * "YYYY-MM"). NULL berarti target buatan guru. Target yang diedit guru
     * dikembalikan ke NULL sehingga tidak ditimpa lagi oleh perhitungan otomatis.
     */
    public function up(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->string('auto_month', 7)->nullable()->after('notes');
            $table->index(['student_id', 'auto_month']);
        });
    }

    public function down(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'auto_month']);
            $table->dropColumn('auto_month');
        });
    }
};
