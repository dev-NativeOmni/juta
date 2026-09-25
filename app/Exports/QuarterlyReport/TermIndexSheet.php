<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Term-Indeks": rekap akhir triwulan per murid, dikelompokkan per tingkat
 * kelas (pita hijau/kuning/oranye) lalu per kelas/halaqoh -- sama seperti tab
 * "Term / Indeks (DNS)" di layar dan template Excel sekolah.
 */
class TermIndexSheet implements FromArray, ShouldAutoSize, WithStrictNullComparison, WithStyles, WithTitle
{
    use GradeBanding;

    /** @var array<int, string> nomor baris pita tingkat => warna hex */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerRows = [];

    /** @var int[] */
    private array $summaryRows = [];

    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Term-Indeks';
    }

    public function array(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
            $rows[] = ["KELAS {$grade}"];
            $this->gradeRows[++$row] = $this->gradeColor($grade);

            foreach ($halaqahs as $halaqah) {
                $rows[] = ["Kelas: {$halaqah['class_room_name']}  |  Musyrif: {$halaqah['musyrif']}"];
                $this->classRows[] = ++$row;

                $rows[] = [
                    'No', 'Nama Murid', 'Level',
                    'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat',
                    'Capaian Baris', 'Target Baris', 'Ketercapaian',
                    'Alpa', 'Izin', 'Sakit', 'Pelanggaran',
                ];
                $this->headerRows[] = ++$row;

                foreach ($halaqah['term_records'] as $idx => $termRow) {
                    $rows[] = [
                        $idx + 1,
                        $termRow['name'],
                        $termRow['level'],
                        $termRow['target_surah'],
                        $termRow['target_ayat'],
                        $termRow['capaian_surah'],
                        $termRow['capaian_ayat'],
                        $termRow['total_lines'],
                        $termRow['target_lines'],
                        $termRow['is_tuntas'] ? 'Tuntas' : 'Tidak Tuntas',
                        $termRow['alpa'],
                        $termRow['izin'],
                        $termRow['sakit'],
                        $termRow['pelanggaran'],
                    ];
                    $row++;
                }

                $total = count($halaqah['term_records']);
                $tuntas = collect($halaqah['term_records'])->where('is_tuntas', true)->count();
                $tuntasPct = $total > 0 ? round(($tuntas / $total) * 100) : 0;
                $summaryRow = ['', '', '', '', '', '', '', '', '', ''];
                $summaryRow[0] = "Tuntas: {$tuntas} ({$tuntasPct}%)";
                $summaryRow[9] = 'Tidak Tuntas: '.($total - $tuntas).' ('.(100 - $tuntasPct).'%)';
                $rows[] = $summaryRow;
                $this->summaryRows[] = ++$row;

                $rows[] = [''];
                $row++;
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->gradeRows as $r => $color) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $color]],
            ];
        }

        foreach ($this->classRows as $r) {
            $styles[$r] = ['font' => ['bold' => true, 'italic' => true]];
        }

        foreach ($this->headerRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        foreach ($this->summaryRows as $r) {
            $styles[$r] = ['font' => ['bold' => true]];
        }

        return $styles;
    }
}
