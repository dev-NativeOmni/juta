<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use App\Exports\QuarterlyReport\Concerns\PekanLabeling;
use App\Exports\QuarterlyReport\Concerns\SignatureBlock;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Setoran" (Capaian Hafalan): grid per bulan > tingkat kelas > kelas/halaqoh,
 * dengan header gabungan "PEKAN N" (Surah, Ayat, Jumlah Baris, Nilai, Kehadiran) dan
 * "REKAPAN AKHIR BULAN" -- sama seperti sheet "CAPAIAN HAFALAN" di template sekolah.
 * Tahfizh memakai grid per hari (Senin-Jumat) per pekan.
 */
class SetoranSheet implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use GradeBanding, PekanLabeling, SignatureBlock;

    private const REGULER_SUBCOLS = ['Surah', 'Ayat', 'Jumlah Baris', 'Nilai', 'Kehadiran'];

    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerTopRows = [];

    /** @var string[] daftar range merge cell, mis. "D5:H5" */
    private array $mergeRanges = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
        private readonly array $signatureContext = [],
    ) {}

    public function title(): string
    {
        return 'Setoran';
    }

    public function array(): array
    {
        return $this->isTahfizhProgram ? $this->buildTahfizhGrid() : $this->buildRegulerGrid();
    }

    private function buildRegulerGrid(): array
    {
        $rows = [];
        $row = 0;
        $months = $this->halaqahData[0]['monthly'] ?? [];

        foreach ($months as $mCode => $firstMonth) {
            $rows[] = ["BULAN {$firstMonth['label']}"];
            $this->monthRows[] = ++$row;

            foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
                $rows[] = ["KELAS {$grade}"];
                $this->gradeRows[++$row] = $this->gradeColor($grade);

                foreach ($halaqahs as $halaqah) {
                    $rows[] = ["Kelas: {$halaqah['class_room_name']}  |  Musyrif: {$halaqah['musyrif']}"];
                    $this->classRows[] = ++$row;

                    $headerTopRow = ++$row;
                    $this->headerTopRows[] = $headerTopRow;
                    $rows[] = $this->regulerHeaderTop($halaqah['monthly'][$mCode]['pekan_dates'] ?? []);
                    $rows[] = $this->regulerHeaderSub();
                    $row++; // baris sub-header kedua

                    $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'C'.$headerTopRow.':C'.($headerTopRow + 1);
                    for ($p = 0; $p < 5; $p++) {
                        $start = Coordinate::stringFromColumnIndex(4 + $p * 5);
                        $end = Coordinate::stringFromColumnIndex(8 + $p * 5);
                        $this->mergeRanges[] = "{$start}{$headerTopRow}:{$end}{$headerTopRow}";
                    }
                    $this->mergeRanges[] = 'AC'.$headerTopRow.':AD'.$headerTopRow;

                    $month = $halaqah['monthly'][$mCode];
                    foreach ($month['reguler_records'] as $idx => $record) {
                        $sPres = $month['presensi'][$record['student_id']] ?? ['hadir' => 0];

                        $line = [$idx + 1, $record['name'], $record['level']];
                        for ($p = 1; $p <= 5; $p++) {
                            $pekan = $record['pekan'][$p];
                            if ($pekan['kehadiran'] !== 'Hadir') {
                                $line = array_merge($line, [$pekan['kehadiran'], '', '', '', $pekan['kehadiran']]);
                            } else {
                                $line = array_merge($line, [$pekan['surah'], $pekan['ayat'], $pekan['baris'], $pekan['nilai'], 'Hadir']);
                            }
                        }
                        $line[] = "{$record['total_lines']} Baris";
                        $line[] = "{$sPres['hadir']}x Hadir";

                        $rows[] = $line;
                        $row++;
                    }

                    $rows[] = [''];
                    $row++;
                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['U', 'AD']);
                }
            }
        }

        return $rows;
    }

    private function regulerHeaderTop(array $pekanDates): array
    {
        $row = ['No', 'Nama Murid', 'Level'];
        for ($p = 1; $p <= 5; $p++) {
            $row = array_merge($row, [$this->pekanLabel($p, $pekanDates), '', '', '', '']);
        }

        return array_merge($row, ['REKAPAN AKHIR BULAN', '']);
    }

    private function regulerHeaderSub(): array
    {
        $row = ['', '', ''];
        for ($p = 1; $p <= 5; $p++) {
            $row = array_merge($row, self::REGULER_SUBCOLS);
        }

        return array_merge($row, ['Capaian Baris', 'Rekap Kehadiran']);
    }

    private function buildTahfizhGrid(): array
    {
        $rows = [];
        $row = 0;
        $months = $this->halaqahData[0]['monthly'] ?? [];

        foreach ($months as $mCode => $firstMonth) {
            $rows[] = ["BULAN {$firstMonth['label']}"];
            $this->monthRows[] = ++$row;

            foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
                $rows[] = ["KELAS {$grade}"];
                $this->gradeRows[++$row] = $this->gradeColor($grade);

                foreach ($halaqahs as $halaqah) {
                    $rows[] = ["Kelas: {$halaqah['class_room_name']}  |  Musyrif: {$halaqah['musyrif']}"];
                    $this->classRows[] = ++$row;

                    $pekanDatesForClass = $halaqah['monthly'][$mCode]['pekan_dates'] ?? [];

                    for ($p = 1; $p <= 5; $p++) {
                        $headerTopRow = ++$row;
                        $this->headerTopRows[] = $headerTopRow;
                        $rows[] = array_merge(['No', 'Nama Murid', 'Level', $this->pekanLabel($p, $pekanDatesForClass), '', '', '', '', 'Rekap'], ['']);
                        $rows[] = array_merge(['', '', ''], self::DAYS, ['Baris', 'Nilai']);
                        $row++;

                        $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                        $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);
                        $this->mergeRanges[] = 'C'.$headerTopRow.':C'.($headerTopRow + 1);
                        $this->mergeRanges[] = 'D'.$headerTopRow.':H'.$headerTopRow;
                        $this->mergeRanges[] = 'I'.$headerTopRow.':J'.$headerTopRow;

                        $month = $halaqah['monthly'][$mCode];
                        foreach ($month['tahfizh_records'] as $idx => $record) {
                            $wRecord = $record['pekan'][$p];
                            $line = [$idx + 1, $record['name'], $record['level']];

                            foreach (self::DAYS as $dayName) {
                                $line[] = $this->tahfizhCell($wRecord['days'][$dayName]);
                            }

                            $line[] = "{$wRecord['week_lines']} Baris";
                            $line[] = 'A';
                            $rows[] = $line;
                            $row++;
                        }

                        $rows[] = [''];
                        $row++;
                    }

                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['G', 'J']);
                }
            }
        }

        return $rows;
    }

    private function tahfizhCell(array $day): string
    {
        if (in_array($day['surah'], ['Libur', 'Belum di input'], true) || $day['baris'] == 0) {
            return $day['surah'];
        }

        $ayat = $day['ayat_start'] !== '' ? "{$day['ayat_start']}-{$day['ayat_end']} " : '';

        return trim("{$day['surah']} {$ayat}({$day['baris']} Brs, Nilai {$day['nilai']})");
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->monthRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E3A8A']],
            ];
        }

        foreach ($this->gradeRows as $r => $color) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $color]],
            ];
        }

        foreach ($this->classRows as $r) {
            $styles[$r] = ['font' => ['bold' => true, 'italic' => true]];
        }

        foreach ($this->headerTopRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $styles[$r + 1] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                foreach ($this->mergeRanges as $range) {
                    $sheet->mergeCells($range);
                }
                $this->applySignatureBlocks($sheet);
            },
        ];
    }
}
