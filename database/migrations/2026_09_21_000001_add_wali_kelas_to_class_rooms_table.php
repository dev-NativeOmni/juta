<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu wali kelas hanya menangani satu kelas (unique per user_id), sesuai kebutuhan
     * saat ini -- beda dari pola "Pendamping Adab" yang banyak-ke-banyak.
     */
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->foreignId('wali_kelas_user_id')
                ->nullable()
                ->after('pendamping_adab_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->unique('wali_kelas_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wali_kelas_user_id');
        });
    }
};
