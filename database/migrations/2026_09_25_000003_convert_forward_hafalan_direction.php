<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Arah hafalan kini menyimpan titik pindah ke depan ('front_29' / 'front_28' / 'front_27').
 * Nilai lama 'forward' (pindah setelah Juz 27) diubah ke 'front_27'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->where('hafalan_direction', 'forward')->update(['hafalan_direction' => 'front_27']);
    }

    public function down(): void
    {
        DB::table('students')->whereIn('hafalan_direction', ['front_29', 'front_28', 'front_27'])->update(['hafalan_direction' => 'forward']);
    }
};
