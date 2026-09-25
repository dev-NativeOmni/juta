<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidates ummi_records rows that were split one-row-per-surah back into
     * a single header row per session, moving the surah/ayah/baris data into the
     * new ummi_record_surahs child table.
     *
     * Rows are grouped by every "header" field (student, teacher, tanggal, tatap_muka,
     * jilid, halaman, materi, nilai, disimak_guru, disimak_ortu, keterangan). Rows that
     * genuinely belong to the same input session always share identical values in all
     * of these fields (confirmed from QuickInputController::storeUmmi() and
     * SpreadsheetInputController::saveUmmiRecords(), the only two places that create
     * these rows). If a row was later edited individually and its header fields now
     * differ from its former siblings, it simply stays ungrouped as its own header —
     * no data is lost or merged incorrectly either way.
     */
    public function up(): void
    {
        $rows = DB::table('ummi_records')->orderBy('id')->get();

        $groups = [];
        foreach ($rows as $row) {
            $key = implode("\x1F", [
                $row->student_id,
                $row->teacher_id,
                $row->tanggal,
                $row->tatap_muka ?? '\0',
                $row->ummi_jilid ?? '\0',
                $row->ummi_halaman ?? '\0',
                $row->materi ?? '\0',
                $row->nilai ?? '\0',
                $row->disimak_guru ?? '\0',
                $row->disimak_ortu ?? '\0',
                $row->keterangan ?? '\0',
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

                if ($row->hafalan_surah_id !== null) {
                    $childRows[] = [
                        'ummi_record_id' => $header->id,
                        'surah_id' => $row->hafalan_surah_id,
                        'hafalan_ayah' => $row->hafalan_ayah,
                        'baris' => $row->baris,
                        'sort_order' => $sortOrder++,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }
            }
        }

        foreach (array_chunk($childRows, 500) as $chunk) {
            DB::table('ummi_record_surahs')->insert($chunk);
        }

        foreach (array_chunk($idsToDelete, 500) as $chunk) {
            DB::table('ummi_records')->whereIn('id', $chunk)->delete();
        }

        Schema::table('ummi_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hafalan_surah_id');
            $table->dropColumn(['hafalan_ayah', 'baris']);
        });
    }

    public function down(): void
    {
        Schema::table('ummi_records', function (Blueprint $table) {
            $table->foreignId('hafalan_surah_id')->nullable()->after('tanggal')->constrained('surahs')->nullOnDelete();
            $table->string('hafalan_ayah', 100)->nullable()->after('hafalan_surah_id');
            $table->decimal('baris', 5, 2)->nullable()->after('nilai');
        });

        $children = DB::table('ummi_record_surahs')->orderBy('ummi_record_id')->orderBy('sort_order')->get();
        $childrenByHeader = [];
        foreach ($children as $child) {
            $childrenByHeader[$child->ummi_record_id][] = $child;
        }

        foreach ($childrenByHeader as $headerId => $childList) {
            $header = DB::table('ummi_records')->where('id', $headerId)->first();
            if (! $header) {
                continue;
            }

            $first = array_shift($childList);
            DB::table('ummi_records')->where('id', $headerId)->update([
                'hafalan_surah_id' => $first->surah_id,
                'hafalan_ayah' => $first->hafalan_ayah,
                'baris' => $first->baris,
            ]);

            foreach ($childList as $extra) {
                DB::table('ummi_records')->insert([
                    'student_id' => $header->student_id,
                    'teacher_id' => $header->teacher_id,
                    'tatap_muka' => $header->tatap_muka,
                    'tanggal' => $header->tanggal,
                    'hafalan_surah_id' => $extra->surah_id,
                    'hafalan_ayah' => $extra->hafalan_ayah,
                    'ummi_jilid' => $header->ummi_jilid,
                    'ummi_halaman' => $header->ummi_halaman,
                    'materi' => $header->materi,
                    'nilai' => $header->nilai,
                    'disimak_guru' => $header->disimak_guru,
                    'disimak_ortu' => $header->disimak_ortu,
                    'keterangan' => $header->keterangan,
                    'baris' => $extra->baris,
                    'created_at' => $extra->created_at,
                    'updated_at' => $extra->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('ummi_record_surahs');
    }
};
