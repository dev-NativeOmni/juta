<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal hari Tahfizh sebuah kelas untuk satu pekan (Senin = week_start). Tanpa baris,
 * pekan itu memakai jadwal default kelas (class_rooms.tahfizh_days).
 *
 * - is_custom: jadwal khusus yang diatur admin (bukan salinan default).
 * - Pekan yang sudah lewat otomatis terkunci; locked_at = dikunci manual lebih awal;
 *   unlocked = dibuka admin supaya pekan lampau bisa diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_week_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->date('week_start');
            $table->json('days');
            $table->boolean('is_custom')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('unlocked')->default(false);
            $table->timestamps();

            $table->unique(['class_room_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_week_schedules');
    }
};
