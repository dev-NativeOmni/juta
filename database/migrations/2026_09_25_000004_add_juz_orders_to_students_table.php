<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi manual urutan di dalam juz per murid: {"27": "desc", ...}. Juz yang tidak
 * tercantum memakai deteksi otomatis dari setoran (App\Services\HafalanProgressService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->json('juz_orders')->nullable()->after('hafalan_direction');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('juz_orders');
        });
    }
};
