<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Urutan hafalan sekolah. Juz 30 sampai juz wajib (Pengaturan Target, default 29) harus
 * dulu; setelah itu murid memilih (Student::hafalan_direction), paling lambat setelah
 * batas pindah (default Juz 27):
 * - 'backward' (default): terus ke belakang, Juz 28 -> 27 -> 26 -> ... -> 1;
 * - 'front_29' / 'front_28' / 'front_27': setelah juz itu pindah ke depan,
 *   Juz 1 -> 2 -> ... sampai juz sebelum titik pindah (mis. front_29: 30, 29, 1..28).
 *
 * Di dalam tiap juz urutan surahnya bisa dari awal ('asc', mis. Juz 29: Al-Mulk ->
 * Al-Mursalat) atau dari akhir ('desc', mis. Juz 27: Al-Hadid -> Adz-Dzariyat 31); ayat
 * dalam satu surah selalu maju. Default: Juz 30 'desc', juz lain 'asc'; arah sebenarnya
 * per murid dideteksi dari setoran (App\Services\HafalanProgressService).
 *
 * Semua juz fleksibel: ayat yang sudah lulus disetor (cakupan) dilewati, jadi target
 * hanya menghitung ayat yang belum dihafal.
 *
 * Batas juz sama dengan migrasi populate_juz_in_ayahs_table.
 */
class HafalanOrder
{
    public const BACKWARD = 'backward';

    /** Urutan di dalam juz: dari awal juz / dari akhir juz. */
    public const ASC = 'asc';

    public const DESC = 'desc';

    /** Pindah ke depan setelah Juz 27 (batas paling akhir). */
    public const FORWARD = 'front_27';

    /**
     * Pilihan arah untuk form: nilai => label.
     *
     * @return array<string, string>
     */
    public static function directionOptions(): array
    {
        $options = [self::BACKWARD => 'Lanjut ke belakang (Juz 28, 27, 26, …)'];
        foreach (TargetRules::switchOptions() as $juz) {
            $options["front_{$juz}"] = "Setelah Juz {$juz} pindah ke depan (Juz 1, 2, …)";
        }

        return $options;
    }

    /**
     * Juz setelah mana murid pindah ke depan, atau null bila terus ke belakang.
     */
    public static function switchJuz(?string $direction): ?int
    {
        $direction = self::normalizeDirection($direction);

        return $direction === self::BACKWARD ? null : (int) substr($direction, strlen('front_'));
    }

    /** @var array<string, array<int, int>> arah => [juz => posisi dalam urutan] */
    private static array $sequenceIndex = [];

    /** @var array<int, array<int, array{surah: int, start: int, end: int}>> */
    public const JUZ_RANGES = [
        1 => [['surah' => 1, 'start' => 1, 'end' => 7], ['surah' => 2, 'start' => 1, 'end' => 141]],
        2 => [['surah' => 2, 'start' => 142, 'end' => 252]],
        3 => [['surah' => 2, 'start' => 253, 'end' => 286], ['surah' => 3, 'start' => 1, 'end' => 92]],
        4 => [['surah' => 3, 'start' => 93, 'end' => 200], ['surah' => 4, 'start' => 1, 'end' => 23]],
        5 => [['surah' => 4, 'start' => 24, 'end' => 147]],
        6 => [['surah' => 4, 'start' => 148, 'end' => 176], ['surah' => 5, 'start' => 1, 'end' => 81]],
        7 => [['surah' => 5, 'start' => 82, 'end' => 120], ['surah' => 6, 'start' => 1, 'end' => 110]],
        8 => [['surah' => 6, 'start' => 111, 'end' => 165], ['surah' => 7, 'start' => 1, 'end' => 87]],
        9 => [['surah' => 7, 'start' => 88, 'end' => 206], ['surah' => 8, 'start' => 1, 'end' => 40]],
        10 => [['surah' => 8, 'start' => 41, 'end' => 75], ['surah' => 9, 'start' => 1, 'end' => 92]],
        11 => [['surah' => 9, 'start' => 93, 'end' => 129], ['surah' => 10, 'start' => 1, 'end' => 109], ['surah' => 11, 'start' => 1, 'end' => 5]],
        12 => [['surah' => 11, 'start' => 6, 'end' => 123], ['surah' => 12, 'start' => 1, 'end' => 52]],
        13 => [['surah' => 12, 'start' => 53, 'end' => 111], ['surah' => 13, 'start' => 1, 'end' => 43], ['surah' => 14, 'start' => 1, 'end' => 52]],
        14 => [['surah' => 15, 'start' => 1, 'end' => 99], ['surah' => 16, 'start' => 1, 'end' => 128]],
        15 => [['surah' => 17, 'start' => 1, 'end' => 111], ['surah' => 18, 'start' => 1, 'end' => 74]],
        16 => [['surah' => 18, 'start' => 75, 'end' => 110], ['surah' => 19, 'start' => 1, 'end' => 98], ['surah' => 20, 'start' => 1, 'end' => 135]],
        17 => [['surah' => 21, 'start' => 1, 'end' => 112], ['surah' => 22, 'start' => 1, 'end' => 78]],
        18 => [['surah' => 23, 'start' => 1, 'end' => 118], ['surah' => 24, 'start' => 1, 'end' => 64], ['surah' => 25, 'start' => 1, 'end' => 20]],
        19 => [['surah' => 25, 'start' => 21, 'end' => 77], ['surah' => 26, 'start' => 1, 'end' => 227], ['surah' => 27, 'start' => 1, 'end' => 55]],
        20 => [['surah' => 27, 'start' => 56, 'end' => 93], ['surah' => 28, 'start' => 1, 'end' => 88], ['surah' => 29, 'start' => 1, 'end' => 45]],
        21 => [['surah' => 29, 'start' => 46, 'end' => 69], ['surah' => 30, 'start' => 1, 'end' => 60], ['surah' => 31, 'start' => 1, 'end' => 34], ['surah' => 32, 'start' => 1, 'end' => 30], ['surah' => 33, 'start' => 1, 'end' => 30]],
        22 => [['surah' => 33, 'start' => 31, 'end' => 73], ['surah' => 34, 'start' => 1, 'end' => 54], ['surah' => 35, 'start' => 1, 'end' => 45], ['surah' => 36, 'start' => 1, 'end' => 27]],
        23 => [['surah' => 36, 'start' => 28, 'end' => 83], ['surah' => 37, 'start' => 1, 'end' => 182], ['surah' => 38, 'start' => 1, 'end' => 88], ['surah' => 39, 'start' => 1, 'end' => 31]],
        24 => [['surah' => 39, 'start' => 32, 'end' => 75], ['surah' => 40, 'start' => 1, 'end' => 85], ['surah' => 41, 'start' => 1, 'end' => 46]],
        25 => [['surah' => 41, 'start' => 47, 'end' => 54], ['surah' => 42, 'start' => 1, 'end' => 53], ['surah' => 43, 'start' => 1, 'end' => 89], ['surah' => 44, 'start' => 1, 'end' => 59], ['surah' => 45, 'start' => 1, 'end' => 37]],
        26 => [['surah' => 46, 'start' => 1, 'end' => 35], ['surah' => 47, 'start' => 1, 'end' => 38], ['surah' => 48, 'start' => 1, 'end' => 29], ['surah' => 49, 'start' => 1, 'end' => 18], ['surah' => 50, 'start' => 1, 'end' => 45], ['surah' => 51, 'start' => 1, 'end' => 30]],
        27 => [['surah' => 51, 'start' => 31, 'end' => 60], ['surah' => 52, 'start' => 1, 'end' => 49], ['surah' => 53, 'start' => 1, 'end' => 62], ['surah' => 54, 'start' => 1, 'end' => 55], ['surah' => 55, 'start' => 1, 'end' => 78], ['surah' => 56, 'start' => 1, 'end' => 96], ['surah' => 57, 'start' => 1, 'end' => 29]],
        28 => [['surah' => 58, 'start' => 1, 'end' => 22], ['surah' => 59, 'start' => 1, 'end' => 24], ['surah' => 60, 'start' => 1, 'end' => 13], ['surah' => 61, 'start' => 1, 'end' => 14], ['surah' => 62, 'start' => 1, 'end' => 11], ['surah' => 63, 'start' => 1, 'end' => 11], ['surah' => 64, 'start' => 1, 'end' => 18], ['surah' => 65, 'start' => 1, 'end' => 12], ['surah' => 66, 'start' => 1, 'end' => 12]],
        29 => [['surah' => 67, 'start' => 1, 'end' => 30], ['surah' => 68, 'start' => 1, 'end' => 52], ['surah' => 69, 'start' => 1, 'end' => 52], ['surah' => 70, 'start' => 1, 'end' => 44], ['surah' => 71, 'start' => 1, 'end' => 28], ['surah' => 72, 'start' => 1, 'end' => 28], ['surah' => 73, 'start' => 1, 'end' => 20], ['surah' => 74, 'start' => 1, 'end' => 56], ['surah' => 75, 'start' => 1, 'end' => 40], ['surah' => 76, 'start' => 1, 'end' => 31], ['surah' => 77, 'start' => 1, 'end' => 50]],
        30 => [
            ['surah' => 78, 'start' => 1, 'end' => 40], ['surah' => 79, 'start' => 1, 'end' => 46], ['surah' => 80, 'start' => 1, 'end' => 42], ['surah' => 81, 'start' => 1, 'end' => 29], ['surah' => 82, 'start' => 1, 'end' => 19], ['surah' => 83, 'start' => 1, 'end' => 36], ['surah' => 84, 'start' => 1, 'end' => 25], ['surah' => 85, 'start' => 1, 'end' => 22], ['surah' => 86, 'start' => 1, 'end' => 17], ['surah' => 87, 'start' => 1, 'end' => 19], ['surah' => 88, 'start' => 1, 'end' => 26], ['surah' => 89, 'start' => 1, 'end' => 30], ['surah' => 90, 'start' => 1, 'end' => 20], ['surah' => 91, 'start' => 1, 'end' => 15], ['surah' => 92, 'start' => 1, 'end' => 21], ['surah' => 93, 'start' => 1, 'end' => 11], ['surah' => 94, 'start' => 1, 'end' => 8],  ['surah' => 95, 'start' => 1, 'end' => 8],  ['surah' => 96, 'start' => 1, 'end' => 19], ['surah' => 97, 'start' => 1, 'end' => 5],  ['surah' => 98, 'start' => 1, 'end' => 8],  ['surah' => 99, 'start' => 1, 'end' => 8],  ['surah' => 100, 'start' => 1, 'end' => 11], ['surah' => 101, 'start' => 1, 'end' => 11], ['surah' => 102, 'start' => 1, 'end' => 8],  ['surah' => 103, 'start' => 1, 'end' => 3],  ['surah' => 104, 'start' => 1, 'end' => 9],  ['surah' => 105, 'start' => 1, 'end' => 5],  ['surah' => 106, 'start' => 1, 'end' => 4],  ['surah' => 107, 'start' => 1, 'end' => 7],  ['surah' => 108, 'start' => 1, 'end' => 3],  ['surah' => 109, 'start' => 1, 'end' => 6],  ['surah' => 110, 'start' => 1, 'end' => 3],  ['surah' => 111, 'start' => 1, 'end' => 5],  ['surah' => 112, 'start' => 1, 'end' => 4],  ['surah' => 113, 'start' => 1, 'end' => 5],  ['surah' => 114, 'start' => 1, 'end' => 6],
        ],
    ];

    /** @var array<int, array<int, array{start: int, end: int, juz: int}>>|null */
    private static ?array $bySurah = null;

    public static function juzOf(int $surah, int $ayah): int
    {
        foreach (self::rangesBySurah()[$surah] ?? [] as $range) {
            if ($ayah >= $range['start'] && $ayah <= $range['end']) {
                return $range['juz'];
            }
        }

        // Ayat di luar batas tercatat: pakai juz bagian surah terdekat.
        $ranges = self::rangesBySurah()[$surah] ?? [['juz' => 30]];

        return $ayah < ($ranges[0]['start'] ?? 1) ? $ranges[0]['juz'] : end($ranges)['juz'];
    }

    public static function normalizeDirection(?string $direction): string
    {
        if ($direction === 'forward') {
            return self::FORWARD; // nilai lama: pindah setelah Juz 27
        }

        // Titik pindah yang sudah tersimpan tetap sah walau pilihan di pengaturan berubah.
        return preg_match('/^front_(\d{1,2})$/', (string) $direction, $m) && (int) $m[1] >= 2 && (int) $m[1] <= 30
            ? $direction
            : self::BACKWARD;
    }

    /**
     * Urutan juz menurut arah, mis. backward: 30..1; front_29: 30, 29, 1..28;
     * front_27: 30, 29, 28, 27, 1..26.
     *
     * @return array<int, int>
     */
    public static function juzSequence(?string $direction = self::BACKWARD): array
    {
        $switch = self::switchJuz($direction);

        return $switch === null
            ? range(30, 1)
            : array_merge(range(30, $switch), range(1, $switch - 1));
    }

    /**
     * Posisi juz dalam urutan hafalan (0 = Juz 30).
     */
    public static function juzPosition(int $juz, ?string $direction = self::BACKWARD): int
    {
        $direction = self::normalizeDirection($direction);
        self::$sequenceIndex[$direction] ??= array_flip(self::juzSequence($direction));

        return self::$sequenceIndex[$direction][$juz];
    }

    public static function defaultJuzOrder(int $juz): string
    {
        return $juz === 30 ? self::DESC : self::ASC;
    }

    /**
     * Peringkat posisi dalam urutan hafalan: makin besar = makin jauh.
     * Di dalam juz mengikuti urutan juz itu ($juzOrders, default Juz 30 mundur, lainnya maju).
     *
     * @param  array<int, string>  $juzOrders  juz => 'asc'|'desc'
     */
    public static function rank(int $surah, int $ayah, ?string $direction = self::BACKWARD, array $juzOrders = []): int
    {
        $juz = self::juzOf($surah, $ayah);
        $order = $juzOrders[$juz] ?? self::defaultJuzOrder($juz);
        $inner = $order === self::DESC ? (114 - $surah) * 1000 + $ayah : $surah * 1000 + $ayah;

        return self::juzPosition($juz, $direction) * 1_000_000 + $inner;
    }

    /**
     * Potongan surah satu juz dalam urutan jalan: [surah, ayat awal, ayat akhir].
     *
     * @return array<int, array{0: int, 1: int, 2: int}>
     */
    public static function juzPieces(int $juz, string $order): array
    {
        $ranges = self::JUZ_RANGES[$juz];
        if ($order === self::DESC) {
            $ranges = array_reverse($ranges);
        }

        return array_map(fn ($range) => [$range['surah'], $range['start'], $range['end']], $ranges);
    }

    /**
     * Potongan hafalan berurutan mulai dari titik awal: [surah, ayat_awal, ayat_akhir].
     * Juz titik awal dimulai dari titik itu lalu memutar ke sisa juz tersebut; juz
     * berikutnya mengikuti urutan arah murid. Ayat yang sudah tercakup dilewati.
     *
     * @param  array<int, array<int, array{0: int, 1: int}>>  $covered  cakupan (AyahCoverage) sebelum titik awal
     * @param  Collection<int, mixed>  $surahsByNumber  (tidak dipakai lagi untuk batas; dipertahankan untuk API)
     * @param  array<int, string>  $juzOrders  juz => 'asc'|'desc'
     * @return \Generator<int, array{0: int, 1: int, 2: int}>
     */
    public static function segments(int $startSurah, int $startAyah, array $covered, Collection $surahsByNumber, ?string $direction = self::BACKWARD, array $juzOrders = []): \Generator
    {
        $startJuz = self::juzOf($startSurah, $startAyah);
        $sequence = array_slice(self::juzSequence($direction), self::juzPosition($startJuz, $direction));

        foreach ($sequence as $i => $juz) {
            $pieces = self::juzPieces($juz, $juzOrders[$juz] ?? self::defaultJuzOrder($juz));
            if ($i === 0) {
                $pieces = self::rotateToStart($pieces, $startSurah, max(1, $startAyah));
            }

            foreach ($pieces as [$surah, $from, $to]) {
                foreach (AyahCoverage::uncovered($covered[$surah] ?? [], $from, $to) as [$gapFrom, $gapTo]) {
                    yield [$surah, $gapFrom, $gapTo];
                }
            }
        }
    }

    /**
     * Putar potongan juz supaya dimulai dari titik awal; bagian juz sebelum titik itu
     * (dalam urutan jalan) dikerjakan setelahnya, sebelum pindah ke juz berikutnya.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pieces
     * @return array<int, array{0: int, 1: int, 2: int}>
     */
    private static function rotateToStart(array $pieces, int $startSurah, int $startAyah): array
    {
        foreach ($pieces as $k => [$surah, $from, $to]) {
            if ($surah === $startSurah && $startAyah >= $from && $startAyah <= $to) {
                $rotated = array_merge([[$surah, $startAyah, $to]], array_slice($pieces, $k + 1), array_slice($pieces, 0, $k));
                if ($startAyah > $from) {
                    $rotated[] = [$surah, $from, $startAyah - 1];
                }

                return $rotated;
            }
        }

        return $pieces;
    }

    /**
     * @return array<int, array<int, array{start: int, end: int, juz: int}>>
     */
    private static function rangesBySurah(): array
    {
        if (self::$bySurah === null) {
            self::$bySurah = [];
            foreach (self::JUZ_RANGES as $juz => $ranges) {
                foreach ($ranges as $range) {
                    self::$bySurah[$range['surah']][] = ['start' => $range['start'], 'end' => $range['end'], 'juz' => $juz];
                }
            }
        }

        return self::$bySurah;
    }
}
