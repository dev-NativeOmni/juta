<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'hafalan_targets_surah_id_ayah_start_ayah_end_index';

    private const NEW_INDEX = 'hafalan_targets_surah_id_ayah_index';

    /**
     * Collapses ayah_start/ayah_end into a single `ayah` column.
     *
     * New semantics: a target represents "memorized from ayah 1 through
     * this ayah" (a cumulative milestone), so existing ranges are
     * backfilled using their ayah_end value.
     *
     * Every step checks current state first so this migration can safely
     * resume if it was previously interrupted partway through (e.g. by
     * the MySQL foreign-key/index ordering issue this revision fixes).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('hafalan_targets', 'ayah')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->unsignedSmallInteger('ayah')->nullable()->after('surah_id');
            });
        }

        if (Schema::hasColumn('hafalan_targets', 'ayah_end')) {
            DB::table('hafalan_targets')
                ->whereNotNull('ayah_end')
                ->whereNull('ayah')
                ->update(['ayah' => DB::raw('ayah_end')]);
        }

        // Create the replacement index BEFORE dropping the old one: MySQL
        // refuses to drop an index that is still the only one supporting
        // the surah_id foreign key.
        if (! Schema::hasIndex('hafalan_targets', self::NEW_INDEX)) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->index(['surah_id', 'ayah'], self::NEW_INDEX);
            });
        }

        if (Schema::hasIndex('hafalan_targets', self::OLD_INDEX)) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->dropIndex(self::OLD_INDEX);
            });
        }

        if (Schema::hasColumn('hafalan_targets', 'ayah_start')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->dropColumn(['ayah_start', 'ayah_end']);
            });
        }
    }

    /**
     * Rollback is lossy for ayah_start: the original start of the range
     * cannot be reconstructed from a single `ayah` value, so it is
     * restored as 1 for every row (documented data loss).
     */
    public function down(): void
    {
        if (! Schema::hasColumn('hafalan_targets', 'ayah_start')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->unsignedSmallInteger('ayah_start')->nullable()->after('surah_id');
                $table->unsignedSmallInteger('ayah_end')->nullable()->after('ayah_start');
            });
        }

        if (Schema::hasColumn('hafalan_targets', 'ayah')) {
            DB::table('hafalan_targets')->whereNotNull('ayah')->update([
                'ayah_start' => 1,
                'ayah_end' => DB::raw('ayah'),
            ]);
        }

        if (! Schema::hasIndex('hafalan_targets', self::OLD_INDEX)) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->index(['surah_id', 'ayah_start', 'ayah_end'], self::OLD_INDEX);
            });
        }

        if (Schema::hasIndex('hafalan_targets', self::NEW_INDEX)) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->dropIndex(self::NEW_INDEX);
            });
        }

        if (Schema::hasColumn('hafalan_targets', 'ayah')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->dropColumn('ayah');
            });
        }
    }
};
