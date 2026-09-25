<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidates hafalan_records rows that were split one-row-per-surah back into
     * a single header row per submission session, moving the surah/ayah/score/status
     * data into the new hafalan_record_surahs child table.
     *
     * Rows are grouped by every "header" field (student, teacher, notes, submitted_at,
     * deleted_at). Rows that genuinely belong to the same submission always share
     * identical values in these fields (confirmed from HafalanRecordController::store()
     * and SpreadsheetInputController::saveHafalanRecords(), the only two places that
     * create these rows). If a row was later edited individually and its header fields
     * now differ from its former siblings, it simply stays ungrouped as its own header —
     * no data is lost or merged incorrectly either way. Soft-deleted rows are grouped and
     * migrated too (keeping their deleted_at), since the per-surah columns are being
     * dropped from the table regardless and there is no restore feature that depends on
     * their old shape.
     */
    public function up(): void
    {
        // Idempotency guard: if hafalan_record_surahs already has rows, the data
        // half of this migration already ran successfully in a previous attempt
        // (e.g. one interrupted by the MySQL FK/index ordering issue fixed below)
        // -- re-running it would duplicate every child row. Skip straight to the
        // schema changes in that case.
        $alreadyMigratedData = Schema::hasTable('hafalan_record_surahs')
            && DB::table('hafalan_record_surahs')->exists();

        if (! $alreadyMigratedData && Schema::hasColumn('hafalan_records', 'surah_id')) {
            $rows = DB::table('hafalan_records')->orderBy('id')->get();

            $groups = [];
            foreach ($rows as $row) {
                $key = implode("\x1F", [
                    $row->student_id,
                    $row->teacher_id,
                    $row->notes ?? '\0',
                    $row->submitted_at,
                    $row->deleted_at ?? '\0',
                ]);

                $groups[$key][] = $row;
            }

            $childRows = [];
            $idsToDelete = [];

            foreach ($groups as $groupRows) {
                $header = $groupRows[0];
                $sortOrder = 0;

                foreach ($groupRows as $row) {
                    if ($row->id !== $header->id) {
                        $idsToDelete[] = $row->id;
                    }

                    $childRows[] = [
                        'hafalan_record_id' => $header->id,
                        'surah_id' => $row->surah_id,
                        'ayah_start' => $row->ayah_start,
                        'ayah_end' => $row->ayah_end,
                        'submission_type' => $row->submission_type,
                        'score' => $row->score,
                        'status' => $row->status,
                        'baris' => $row->baris,
                        'sort_order' => $sortOrder++,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }
            }

            foreach (array_chunk($childRows, 500) as $chunk) {
                DB::table('hafalan_record_surahs')->insert($chunk);
            }

            foreach (array_chunk($idsToDelete, 500) as $chunk) {
                DB::table('hafalan_records')->whereIn('id', $chunk)->delete();
            }
        }

        // Drop the foreign key constraint BEFORE the composite index: on MySQL,
        // ['surah_id', 'ayah_start', 'ayah_end'] is the only index backing the
        // surah_id FK, so dropping the index first fails with error 1553.
        // SQLite (used in tests) has no such restriction, so this ordering issue
        // only surfaces on production MySQL.
        if (Schema::hasColumn('hafalan_records', 'surah_id')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropForeign(['surah_id']);
            });
        }

        if (Schema::hasIndex('hafalan_records', ['surah_id', 'ayah_start', 'ayah_end'])) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex(['surah_id', 'ayah_start', 'ayah_end']);
            });
        }

        if (Schema::hasIndex('hafalan_records', ['status', 'submission_type'])) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex(['status', 'submission_type']);
            });
        }

        if (Schema::hasIndex('hafalan_records', 'idx_hafalan_student_status_date')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex('idx_hafalan_student_status_date');
            });
        }

        if (Schema::hasIndex('hafalan_records', 'idx_hafalan_date_status')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex('idx_hafalan_date_status');
            });
        }

        if (Schema::hasColumn('hafalan_records', 'surah_id')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropColumn(['surah_id', 'ayah_start', 'ayah_end', 'submission_type', 'score', 'status', 'baris']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('hafalan_records', function (Blueprint $table) {
            $table->foreignId('surah_id')->after('teacher_id')->constrained('surahs')->restrictOnDelete();
            $table->unsignedSmallInteger('ayah_start')->after('surah_id');
            $table->unsignedSmallInteger('ayah_end')->after('ayah_start');
            $table->enum('submission_type', ['new', 'continuation', 'revision'])->default('new')->after('ayah_end');
            $table->decimal('score', 5, 2)->nullable()->after('submission_type');
            $table->enum('status', ['passed', 'repeat', 'needs_improvement'])->default('needs_improvement')->after('score');
            $table->decimal('baris', 5, 2)->nullable()->after('status');

            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
            $table->index(['status', 'submission_type']);
            $table->index(['student_id', 'status', 'submitted_at'], 'idx_hafalan_student_status_date');
            $table->index(['submitted_at', 'status'], 'idx_hafalan_date_status');
        });

        $children = DB::table('hafalan_record_surahs')->orderBy('hafalan_record_id')->orderBy('sort_order')->get();
        $childrenByHeader = [];
        foreach ($children as $child) {
            $childrenByHeader[$child->hafalan_record_id][] = $child;
        }

        foreach ($childrenByHeader as $headerId => $childList) {
            $header = DB::table('hafalan_records')->where('id', $headerId)->first();
            if (! $header) {
                continue;
            }

            $first = array_shift($childList);
            DB::table('hafalan_records')->where('id', $headerId)->update([
                'surah_id' => $first->surah_id,
                'ayah_start' => $first->ayah_start,
                'ayah_end' => $first->ayah_end,
                'submission_type' => $first->submission_type,
                'score' => $first->score,
                'status' => $first->status,
                'baris' => $first->baris,
            ]);

            foreach ($childList as $extra) {
                DB::table('hafalan_records')->insert([
                    'student_id' => $header->student_id,
                    'teacher_id' => $header->teacher_id,
                    'surah_id' => $extra->surah_id,
                    'ayah_start' => $extra->ayah_start,
                    'ayah_end' => $extra->ayah_end,
                    'submission_type' => $extra->submission_type,
                    'score' => $extra->score,
                    'status' => $extra->status,
                    'baris' => $extra->baris,
                    'notes' => $header->notes,
                    'submitted_at' => $header->submitted_at,
                    'deleted_at' => $header->deleted_at,
                    'created_at' => $extra->created_at,
                    'updated_at' => $extra->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('hafalan_record_surahs');
    }
};
