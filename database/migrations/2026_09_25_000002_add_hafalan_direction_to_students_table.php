<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arah hafalan murid setelah menyelesaikan bagian belakang (Juz 30 -> 27):
 * 'backward' = lanjut Juz 26, 25, ... ; 'forward' = pindah ke Juz 1, 2, ...
 * Lihat App\Support\HafalanOrder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('hafalan_direction', 10)->default('backward')->after('tahfizh_level');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('hafalan_direction');
        });
    }
};
