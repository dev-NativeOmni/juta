<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use App\Exports\QuarterlyReport\Concerns\SignatureBlock;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Jurnal": jurnal tatap muka per bulan > tingkat kelas > kelas/halaqoh,
 * sama seperti sheet "JURNAL" di template sekolah.
 */
class JurnalSheet implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use GradeBanding, SignatureBlock;

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerRows = [];

    /** @var string[] */
    private array $mergeRanges = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly array $signatureContext = [],
    ) {}

    public function title(): string
    {
        return 'Jurnal';
    }

    public function array(): array
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

                    $rows[] = ['No', 'Hari / Tanggal', 'Materi', 'Jumlah Murid Hadir', 'Paraf'];
                    $this->headerRows[] = ++$row;

                    foreach ($halaqah['monthly'][$mCode]['jurnal'] as $jIdx => $entry) {
                        $rows[] = [
                            $jIdx + 1,
                            $entry['tanggal'],
                            $entry['materi'],
                            $entry['jumlah_murid'] ?? '-',
                            $entry['paraf'],
                        ];
                        $row++;
                    }

                    $rows[] = [''];
                    $row++;
                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E']);
                }
            }
        }

        return $rows;
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

        foreach ($this->headerRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        return $styles;
    }
}
