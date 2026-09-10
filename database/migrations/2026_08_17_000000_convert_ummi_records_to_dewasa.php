<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Conversion mapping logic for Jilid Ummi Biasa (1-6) -> Jilid Ummi Dewasa (1-3 @ 40 pages):
        // Jilid 1 -> Jilid 1 (Dewasa), Hal 1-20
        // Jilid 2 -> Jilid 1 (Dewasa), Hal 21-40 (Page + 20)
        // Jilid 3 -> Jilid 2 (Dewasa), Hal 1-20
        // Jilid 4 -> Jilid 2 (Dewasa), Hal 21-40 (Page + 20)
        // Jilid 5 -> Jilid 3 (Dewasa), Hal 1-20
        // Jilid 6 -> Jilid 3 (Dewasa), Hal 21-40 (Page + 20)

        $mapJilid = function ($jilidStr, $halamanStr) {
            if (! $jilidStr) {
                return [$jilidStr, $halamanStr];
            }

            if (! preg_match('/jilid\s*(\d+)/i', $jilidStr, $m)) {
                return [$jilidStr, $halamanStr];
            }

            $num = (int) $m[1];
            preg_match('/(\d+)/', (string) $halamanStr, $mHal);
            $hal = isset($mHal[1]) ? (int) $mHal[1] : 0;

            if ($num === 1) {
                $newJilid = 'Jilid 1';
                $newHal = $hal > 0 ? $hal : $halamanStr;
            } elseif ($num === 2) {
                $newJilid = 'Jilid 1';
                $newHal = $hal > 0 ? ($hal + 20) : '21-40';
            } elseif ($num === 3) {
                $newJilid = 'Jilid 2';
                $newHal = $hal > 0 ? $hal : $halamanStr;
            } elseif ($num === 4) {
                $newJilid = 'Jilid 2';
                $newHal = $hal > 0 ? ($hal + 20) : '21-40';
            } elseif ($num === 5) {
                $newJilid = 'Jilid 3';
                $newHal = $hal > 0 ? $hal : $halamanStr;
            } elseif ($num === 6) {
                $newJilid = 'Jilid 3';
                $newHal = $hal > 0 ? ($hal + 20) : '21-40';
            } else {
                $newJilid = $jilidStr;
                $newHal = $halamanStr;
            }

            return [$newJilid, (string) $newHal];
        };

        try {
            $ummiRecords = DB::table('ummi_records')->get();
            foreach ($ummiRecords as $rec) {
                [$nJ, $nH] = $mapJilid($rec->ummi_jilid, $rec->ummi_halaman);
                if ($nJ !== $rec->ummi_jilid || $nH !== $rec->ummi_halaman) {
                    DB::table('ummi_records')->where('id', $rec->id)->update([
                        'ummi_jilid' => $nJ,
                        'ummi_halaman' => $nH,
                    ]);
                }
            }

            $targets = DB::table('hafalan_targets')->whereNotNull('ummi_jilid')->get();
            foreach ($targets as $t) {
                [$nJ, $nH] = $mapJilid($t->ummi_jilid, $t->halaman_buku);
                if ($nJ !== $t->ummi_jilid || $nH !== $t->halaman_buku) {
                    DB::table('hafalan_targets')->where('id', $t->id)->update([
                        'ummi_jilid' => $nJ,
                        'halaman_buku' => $nH,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if table not present in current env
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
