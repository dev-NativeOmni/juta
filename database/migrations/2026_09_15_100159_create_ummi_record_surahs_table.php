<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ummi_record_surahs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ummi_record_id')
                ->constrained('ummi_records')
                ->cascadeOnDelete();

            $table->foreignId('surah_id')
                ->constrained('surahs')
                ->restrictOnDelete();

            $table->string('hafalan_ayah', 100)->nullable();
            $table->decimal('baris', 5, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['ummi_record_id', 'sort_order']);
            $table->index('surah_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ummi_record_surahs');
    }
};
