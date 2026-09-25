<?php

namespace App\Exports\QuarterlyReport\Concerns;

/**
 * Label kolom "Pekan N" berisi hari & tanggal pertemuan aktif sungguhan
 * (jadwal kelas x kalender akademik), bukan sekadar nomor pekan generik.
 */
trait PekanLabeling
{
    private function pekanLabel(int $p, array $pekanDates): string
    {
        $dates = $pekanDates[$p] ?? [];

        if (empty($dates)) {
            return "PEKAN {$p} (Libur)";
        }

        return "PEKAN {$p} (".implode(' & ', $dates).')';
    }
}
