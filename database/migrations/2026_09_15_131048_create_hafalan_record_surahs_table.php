<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hafalan_record_surahs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hafalan_record_id')
                ->constrained('hafalan_records')
                ->cascadeOnDelete();

            $table->foreignId('surah_id')
                ->constrained('surahs')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('ayah_start');
            $table->unsignedSmallInteger('ayah_end');

            $table->enum('submission_type', [
                'new',
                'continuation',
                'revision',
            ])->default('new');

            $table->decimal('score', 5, 2)->nullable();

            $table->enum('status', [
                'passed',
                'repeat',
                'needs_improvement',
            ])->default('needs_improvement');

            $table->decimal('baris', 5, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['hafalan_record_id', 'sort_order']);
            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
            $table->index(['status', 'submission_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hafalan_record_surahs');
    }
};
