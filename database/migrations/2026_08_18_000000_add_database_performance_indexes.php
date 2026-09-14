<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('hafalan_records')) {
            try {
                Schema::table('hafalan_records', function (Blueprint $table) {
                    $table->index(['student_id', 'submitted_at'], 'idx_hafalan_std_subdate');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }

        if (Schema::hasTable('attendances')) {
            try {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->index(['student_id', 'tanggal'], 'idx_att_std_tanggal');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }

            try {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->index(['class_room_id', 'tanggal'], 'idx_att_cls_tanggal');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }

        if (Schema::hasTable('ummi_records')) {
            try {
                Schema::table('ummi_records', function (Blueprint $table) {
                    $table->index(['student_id', 'tanggal'], 'idx_ummi_std_tanggal');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }

        if (Schema::hasTable('murajaah_records')) {
            try {
                // murajaah_records has no submitted_at column — it's reviewed_at.
                // This previously failed on every run; on Postgres a failed
                // statement poisons the rest of the transaction, so
                // subsequent queries in the same request/test would also
                // fail even though the error itself was swallowed here.
                Schema::table('murajaah_records', function (Blueprint $table) {
                    $table->index(['student_id', 'reviewed_at'], 'idx_murajaah_std_subdate');
                });
            } catch (Throwable $e) {
                // Index already exists, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hafalan_records')) {
            try {
                Schema::table('hafalan_records', function (Blueprint $table) {
                    $table->dropIndex('idx_hafalan_std_subdate');
                });
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasTable('attendances')) {
            try {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->dropIndex('idx_att_std_tanggal');
                    $table->dropIndex('idx_att_cls_tanggal');
                });
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasTable('ummi_records')) {
            try {
                Schema::table('ummi_records', function (Blueprint $table) {
                    $table->dropIndex('idx_ummi_std_tanggal');
                });
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasTable('murajaah_records')) {
            try {
                Schema::table('murajaah_records', function (Blueprint $table) {
                    $table->dropIndex('idx_murajaah_std_subdate');
                });
            } catch (Throwable $e) {
            }
        }
    }
};
