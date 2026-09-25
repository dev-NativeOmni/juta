<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use App\Exports\QuarterlyReport\Concerns\PekanLabeling;
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
 * Sheet "Presensi": grid per bulan > tingkat kelas > kelas/halaqoh, dengan header
 * gabungan "Tanggal Tatap Muka" (Pekan 1-5) dan "Rekap Kehadiran" (Hadir/Izin/
 * Sakit/Alpa) untuk Reguler, atau 12 pertemuan per bulan untuk Tahfizh -- sama
 * seperti sheet "PRESENSI" di template sekolah, plus baris JUMLAH per kelas.
 */
class PresensiSheet implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use GradeBanding, PekanLabeling;

    private const STATUS_MAP = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa', '-' => '-'];

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerTopRows = [];

    /** @var int[] */
    private array $totalRows = [];

    /** @var string[] */
    private array $mergeRanges = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
    ) {}

    public function title(): string
    {
        return 'Presensi';
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

                    $pekanDates = $halaqah['monthly'][$mCode]['pekan_dates'] ?? [];

                    $headerTopRow = ++$row;
                    $this->headerTopRows[] = $headerTopRow;
                    $rows[] = ['No', 'Nama Murid', 'Tanggal Tatap Muka', '', '', '', '', 'Rekap Kehadiran', '', '', ''];
                    $rows[] = [
                        '', '',
                        $this->pekanLabel(1, $pekanDates),
                        $this->pekanLabel(2, $pekanDates),
                        $this->pekanLabel(3, $pekanDates),
                        $this->pekanLabel(4, $pekanDates),
                        $this->pekanLabel(5, $pekanDates),
                        'Hadir', 'Izin', 'Sakit', 'Alpa',
                    ];
                    $row++;

                    $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'C'.$headerTopRow.':G'.$headerTopRow;
                    $this->mergeRanges[] = 'H'.$headerTopRow.':K'.$headerTopRow;

                    $month = $halaqah['monthly'][$mCode];
                    $totals = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];

                    foreach ($halaqah['students'] as $idx => $student) {
                        $sPres = $month['presensi'][$student->id] ?? null;
                        if (! $sPres) {
                            continue;
                        }

                        $rows[] = [
                            $idx + 1,
                            $student->name,
                            $sPres['pekan'][1] ?? '-',
                            $sPres['pekan'][2] ?? '-',
                            $sPres['pekan'][3] ?? '-',
                            $sPres['pekan'][4] ?? '-',
                            $sPres['pekan'][5] ?? '-',
                            $sPres['hadir'],
                            $sPres['izin'],
                            $sPres['sakit'],
                            $sPres['alpa'],
                        ];
                        $row++;

                        $totals['hadir'] += $sPres['hadir'];
                        $totals['izin'] += $sPres['izin'];
                        $totals['sakit'] += $sPres['sakit'];
                        $totals['alpa'] += $sPres['alpa'];
                    }

                    $rows[] = ['JUMLAH', '', '', '', '', '', '', $totals['hadir'], $totals['izin'], $totals['sakit'], $totals['alpa']];
                    $this->totalRows[] = ++$row;

                    $rows[] = [''];
                    $row++;
                }
            }
        }

        return $rows;
    }

    private function buildTahfizhGrid(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
            $rows[] = ["KELAS {$grade}"];
            $this->gradeRows[++$row] = $this->gradeColor($grade);

            foreach ($halaqahs as $halaqah) {
                $className = $halaqah['class_room_name'] ?? '-';
                $months = $halaqah['months'] ?? [];

                $rows[] = ["Kelas: {$className}  |  Musyrif: {$halaqah['musyrif']}"];
                $this->classRows[] = ++$row;

                $headerTopRow = ++$row;
                $this->headerTopRows[] = $headerTopRow;
                $topHeader = ['No', 'Nama Murid'];
                $subHeader = ['', ''];
                foreach ($months as $mName) {
                    $topHeader = array_merge($topHeader, [$mName], array_fill(0, 14, ''));
                    $subHeader = array_merge($subHeader, range(1, 12), ['S', 'I', 'A']);
                }
                $rows[] = $topHeader;
                $rows[] = $subHeader;
                $row++;

                $col = 3; // kolom C = awal blok bulan pertama (1-indexed Excel)
                foreach ($months as $mName) {
                    $start = Coordinate::stringFromColumnIndex($col);
                    $end = Coordinate::stringFromColumnIndex($col + 14);
                    $this->mergeRanges[] = "{$start}{$headerTopRow}:{$end}{$headerTopRow}";
                    $col += 15;
                }
                $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);

                foreach ($halaqah['students'] as $idx => $student) {
                    $line = [$idx + 1, $student->name];
                    foreach ($months as $mName) {
                        $sPres = $halaqah['presensi'][$student->id][$mName] ?? null;
                        for ($day = 1; $day <= 12; $day++) {
                            $code = $sPres['days'][$day] ?? '-';
                            $line[] = self::STATUS_MAP[$code] ?? $code;
                        }
                        $line[] = $sPres['sakit'] ?? 0;
                        $line[] = $sPres['izin'] ?? 0;
                        $line[] = $sPres['alpa'] ?? 0;
                    }
                    $rows[] = $line;
                    $row++;
                }

                $rows[] = [''];
                $row++;
            }
        }

        return $rows;
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

        foreach ($this->totalRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F9FAFB']],
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
            },
        ];
    }
}
