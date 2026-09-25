<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci kalender per bulan & cakupan ('tahfizh' / 'adab'): bulan terkunci tidak bisa
 * diubah status liburnya untuk cakupan itu sampai dibuka Super Admin/Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_month_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('scope', 16);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_month_locks');
    }
};
