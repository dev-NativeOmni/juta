<?php

namespace App\Support;

/**
 * Himpunan ayat yang sudah lulus disetor, per surah sebagai rentang tergabung:
 * [nomor surah => [[awal, akhir], ...]] (urut, tidak tumpang tindih).
 */
class AyahCoverage
{
    /**
     * @param  iterable<array{0: int, 1: int, 2: int}>  $ranges  [surah, ayat awal, ayat akhir]
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    public static function fromRanges(iterable $ranges): array
    {
        $bySurah = [];
        foreach ($ranges as [$surah, $from, $to]) {
            if ($from > 0 && $to >= $from) {
                $bySurah[(int) $surah][] = [(int) $from, (int) $to];
            }
        }

        return array_map([self::class, 'merge'], $bySurah);
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervals
     * @return array<int, array{0: int, 1: int}>
     */
    public static function merge(array $intervals): array
    {
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($intervals as [$from, $to]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $from <= $merged[$last][1] + 1) {
                $merged[$last][1] = max($merged[$last][1], $to);
            } else {
                $merged[] = [$from, $to];
            }
        }

        return $merged;
    }

    /**
     * Bagian [from, to] yang BELUM tercakup.
     *
     * @param  array<int, array{0: int, 1: int}>  $intervals
     * @return array<int, array{0: int, 1: int}>
     */
    public static function uncovered(array $intervals, int $from, int $to): array
    {
        $gaps = [];
        $cursor = $from;
        foreach ($intervals as [$a, $b]) {
            if ($b < $cursor) {
                continue;
            }
            if ($a > $to) {
                break;
            }
            if ($a > $cursor) {
                $gaps[] = [$cursor, min($a - 1, $to)];
            }
            $cursor = max($cursor, $b + 1);
            if ($cursor > $to) {
                break;
            }
        }
        if ($cursor <= $to) {
            $gaps[] = [$cursor, $to];
        }

        return $gaps;
    }

    /**
     * Bagian [from, to] yang SUDAH tercakup.
     *
     * @param  array<int, array{0: int, 1: int}>  $intervals
     * @return array<int, array{0: int, 1: int}>
     */
    public static function covered(array $intervals, int $from, int $to): array
    {
        $parts = [];
        foreach ($intervals as [$a, $b]) {
            if ($b >= $from && $a <= $to) {
                $parts[] = [max($a, $from), min($b, $to)];
            }
        }

        return $parts;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $intervals
     */
    public static function contains(array $intervals, int $ayah): bool
    {
        foreach ($intervals as [$a, $b]) {
            if ($ayah >= $a && $ayah <= $b) {
                return true;
            }
        }

        return false;
    }
}
