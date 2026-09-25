<?php

namespace App\Exports\QuarterlyReport\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Blok tanda tangan di bawah tiap tabel halaqoh (Jurnal & Capaian Hafalan), seperti
 * template sekolah: kiri "Mengetahui, Kepala Sekolah", kanan "Kota, tanggal / Guru
 * Pengampu", masing-masing dengan gambar tanda tangan (bila sudah diunggah) dan nama.
 * Tanggal = tanggal terakhir triwulan (sama untuk semua blok).
 *
 * $signatureContext (dari QuarterlyReportExport): city, date, headmaster_title, headmaster_name,
 * headmaster_nik, headmaster_signature (path absolut|null), teacher_signatures
 * (berkas relatif => path absolut). Kosong = blok tidak dibuat.
 */
trait SignatureBlock
{
    /** @var array<int, array{cell: string, path: string}> */
    private array $signatureDrawings = [];

    /** @var int[] */
    private array $signatureNameRows = [];

    /**
     * Tambahkan baris blok tanda tangan ke $rows (teks di sel awal rentang kiri/kanan,
     * rentang digabung supaya lebar kolom tabel tidak ikut melebar).
     *
     * @param  array{0: string, 1: string}  $left  kolom awal & akhir blok kiri, mis. ['B', 'C']
     * @param  array{0: string, 1: string}  $right  kolom awal & akhir blok kanan
     */
    private function appendSignatureBlock(array &$rows, int &$row, array $halaqah, array $left, array $right): void
    {
        $ctx = $this->signatureContext;
        if ($ctx === []) {
            return;
        }

        $date = $ctx['date'] ?? '';
        $teacherSignature = $ctx['teacher_signatures'][$halaqah['musyrif_signature'] ?? ''] ?? null;

        $lines = [
            ['Mengetahui,', trim(($ctx['city'] ?? '').', '.$date, ', ')],
            [$ctx['headmaster_title'] ?? 'Kepala Sekolah', 'Guru Pengampu'],
            ['', ''], ['', ''], ['', ''], // ruang gambar tanda tangan
            [$ctx['headmaster_name'] ?? '', $halaqah['musyrif'] ?? ''],
            [! empty($ctx['headmaster_nik']) ? 'NIK. '.$ctx['headmaster_nik'] : '', ''],
        ];

        $leftIdx = Coordinate::columnIndexFromString($left[0]);
        $rightIdx = Coordinate::columnIndexFromString($right[0]);
        $imageRow = $row + 3;

        foreach ($lines as $i => [$leftText, $rightText]) {
            $line = array_fill(0, $rightIdx, '');
            $line[$leftIdx - 1] = $leftText;
            $line[$rightIdx - 1] = $rightText;
            $rows[] = $line;
            $row++;

            $this->mergeRanges[] = "{$left[0]}{$row}:{$left[1]}{$row}";
            $this->mergeRanges[] = "{$right[0]}{$row}:{$right[1]}{$row}";
            if ($i === 5) {
                $this->signatureNameRows[] = $row;
            }
        }

        foreach ([[$left[0], $ctx['headmaster_signature'] ?? null], [$right[0], $teacherSignature]] as [$col, $path]) {
            if ($path) {
                $this->signatureDrawings[] = ['cell' => $col.$imageRow, 'path' => $path];
            }
        }

        $rows[] = [''];
        $row++;
    }

    private function applySignatureBlocks(Worksheet $sheet): void
    {
        foreach ($this->signatureNameRows as $r) {
            $sheet->getStyle("A{$r}:".$sheet->getHighestColumn()."{$r}")->getFont()->setBold(true)->setUnderline(true);
        }

        foreach ($this->signatureDrawings as $index => $drawing) {
            $image = new Drawing;
            $image->setName('Tanda Tangan '.($index + 1));
            $image->setPath($drawing['path']);
            $image->setHeight(55);
            $image->setCoordinates($drawing['cell']);
            $image->setOffsetX(8);
            $image->setWorksheet($sheet);
        }
    }
}
