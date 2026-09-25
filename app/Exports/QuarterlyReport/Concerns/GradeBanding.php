<?php

namespace App\Exports\QuarterlyReport\Concerns;

/**
 * Pita warna per tingkat kelas (hijau=X, kuning=XI, oranye=XII), sama seperti
 * template Excel sekolah yang jadi acuan Laporan Triwulan.
 */
trait GradeBanding
{
    private const GRADE_ORDER = ['X', 'XI', 'XII', 'Lainnya'];

    private const GRADE_COLORS = [
        'X' => '86EFAC',
        'XI' => 'FDE68A',
        'XII' => 'FDBA74',
        'Lainnya' => 'D1D5DB',
    ];

    private function gradeLabel(string $className): string
    {
        if (preg_match('/\bXII\b|\b12\b/i', $className)) {
            return 'XII';
        }
        if (preg_match('/\bXI\b|\b11\b/i', $className)) {
            return 'XI';
        }
        if (preg_match('/\bX\b|\b10\b/i', $className)) {
            return 'X';
        }

        return 'Lainnya';
    }

    private function gradeColor(string $grade): string
    {
        return self::GRADE_COLORS[$grade] ?? self::GRADE_COLORS['Lainnya'];
    }

    /**
     * Kelompokkan halaqahData per tingkat kelas, urut X, XI, XII, Lainnya --
     * urutan asal di dalam satu tingkat tetap dipertahankan.
     *
     * @return array<string, array<int, array>>
     */
    private function groupByGrade(array $halaqahData): array
    {
        $groups = [];
        foreach ($halaqahData as $halaqah) {
            $grade = $this->gradeLabel($halaqah['class_room_name'] ?? '');
            $groups[$grade][] = $halaqah;
        }

        uksort($groups, fn ($a, $b) => array_search($a, self::GRADE_ORDER, true) <=> array_search($b, self::GRADE_ORDER, true));

        return $groups;
    }
}
