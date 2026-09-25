<?php

namespace App\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menghitung posisi target hafalan (surah & ayat) dari titik awal + jumlah baris,
 * memakai hitungan baris mushaf otomatis yang sama dengan setoran
 * (ReportController::calculateLines) dan urutan hafalan sekolah (App\Support\HafalanOrder):
 * Juz 30 (fleksibel), lalu Juz 29, 28, ... masing-masing dari awal juz.
 */
class QuranLineTargetService
{
    /**
     * Posisi target setelah $targetLines baris dari titik awal.
     *
     * @param  Collection<int, Surah>  $surahsByNumber  keyed by surah number
     * @param  array<int, array<int, array{0: int, 1: int}>>  $covered  cakupan sebelum titik awal (dilewati)
     * @param  array<int, string>  $juzOrders
     * @return array{surah: Surah, ayah_start: int, ayah_end: int}|null
     */
    public function targetPosition(int $startSurahNumber, int $startAyah, float $targetLines, Collection $surahsByNumber, array $covered = [], ?string $direction = HafalanOrder::BACKWARD, array $juzOrders = []): ?array
    {
        return $this->walkLines($startSurahNumber, $startAyah, $targetLines, $surahsByNumber, $covered, $direction, $juzOrders)['position'] ?? null;
    }

    /**
     * Telusuri jalur hafalan sejauh $targetLines baris: posisi akhir + potongan ayat yang dilalui.
     *
     * @return array{position: array{surah: Surah, ayah_start: int, ayah_end: int}, pieces: array<int, array{0: int, 1: int, 2: int}>}|null
     */
    public function walkLines(int $startSurahNumber, int $startAyah, float $targetLines, Collection $surahsByNumber, array $covered = [], ?string $direction = HafalanOrder::BACKWARD, array $juzOrders = []): ?array
    {
        if ($targetLines <= 0 || ! $surahsByNumber->has($startSurahNumber)) {
            return null;
        }

        $remaining = $targetLines;
        $pieces = [];

        foreach (HafalanOrder::segments($startSurahNumber, $startAyah, $covered, $surahsByNumber, $direction, $juzOrders) as [$surahNumber, $from, $to]) {
            $surah = $surahsByNumber->get($surahNumber);
            if (! $surah) {
                continue;
            }

            $totalAyah = (int) $surah->total_ayah;
            $to = min($to, $totalAyah);
            if ($from > $to) {
                continue;
            }
            $segmentLines = ReportController::calculateLines($surahNumber, $from, $to, $totalAyah);

            if ($segmentLines >= $remaining) {
                $end = $to;
                for ($ayah = $from; $ayah <= $to; $ayah++) {
                    if (ReportController::calculateLines($surahNumber, $from, $ayah, $totalAyah) >= $remaining) {
                        $end = $ayah;
                        break;
                    }
                }
                $pieces[] = [$surahNumber, $from, $end];

                return ['position' => ['surah' => $surah, 'ayah_start' => $from, 'ayah_end' => $end], 'pieces' => $pieces];
            }

            $pieces[] = [$surahNumber, $from, $to];
            $remaining -= $segmentLines;
        }

        return null;
    }

    /**
     * Potongan ayat dari titik awal sampai (dan termasuk) posisi target menurut jalur hafalan,
     * atau null bila posisi target tidak ada di jalur (mis. sudah dihafal sebelumnya).
     *
     * @return array<int, array{0: int, 1: int, 2: int}>|null
     */
    public function piecesUntil(int $startSurahNumber, int $startAyah, int $targetSurah, int $targetAyah, Collection $surahsByNumber, array $covered = [], ?string $direction = HafalanOrder::BACKWARD, array $juzOrders = []): ?array
    {
        $pieces = [];

        foreach (HafalanOrder::segments($startSurahNumber, $startAyah, $covered, $surahsByNumber, $direction, $juzOrders) as [$surahNumber, $from, $to]) {
            if ($surahNumber === $targetSurah && $targetAyah >= $from && $targetAyah <= $to) {
                $pieces[] = [$surahNumber, $from, $targetAyah];

                return $pieces;
            }
            $pieces[] = [$surahNumber, $from, $to];
        }

        return null;
    }

    /**
     * Jumlah baris potongan ayat; bila $coverage diberikan, hanya bagian yang tercakup.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pieces
     * @param  array<int, array<int, array{0: int, 1: int}>>|null  $coverage
     */
    public function piecesLines(array $pieces, Collection $surahsByNumber, ?array $coverage = null): float
    {
        $lines = 0.0;
        foreach ($pieces as [$surahNumber, $from, $to]) {
            $totalAyah = (int) ($surahsByNumber->get($surahNumber)?->total_ayah ?? 0);
            $parts = $coverage === null ? [[$from, $to]] : AyahCoverage::covered($coverage[$surahNumber] ?? [], $from, $to);
            foreach ($parts as [$a, $b]) {
                $lines += ReportController::calculateLines($surahNumber, $a, $b, $totalAyah);
            }
        }

        return $lines;
    }

    /**
     * Apakah semua ayat pada potongan sudah tercakup.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pieces
     * @param  array<int, array<int, array{0: int, 1: int}>>  $coverage
     */
    public function piecesCovered(array $pieces, array $coverage): bool
    {
        foreach ($pieces as [$surahNumber, $from, $to]) {
            if (AyahCoverage::uncovered($coverage[$surahNumber] ?? [], $from, $to) !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apakah capaian sudah sampai atau melewati target menurut urutan hafalan
     * (Juz 30 -> 27, lalu sesuai arah murid; lihat HafalanOrder::rank()).
     */
    public function hasReached(int $capaianSurahNumber, int $capaianAyah, int $targetSurahNumber, int $targetAyah, ?string $direction = HafalanOrder::BACKWARD, array $juzOrders = []): bool
    {
        return HafalanOrder::rank($capaianSurahNumber, $capaianAyah, $direction, $juzOrders) >= HafalanOrder::rank($targetSurahNumber, $targetAyah, $direction, $juzOrders);
    }

    /**
     * Cari capaian terbaru dari kumpulan setoran (record dengan relasi `surah`).
     * Jika beberapa setoran punya submitted_at yang sama (mis. beberapa surat disetorkan
     * dalam satu sesi), yang dianggap capaian adalah yang posisinya paling jauh menurut
     * urutan hafalan (HafalanOrder::rank) -- bukan sekadar entri pertama yang
     * ter-load, karena urutan itu tidak menjamin urutan pengerjaan sebenarnya.
     *
     * @param  Collection<int, mixed>  $records
     */
    public function latestByPosition(Collection $records, ?string $direction = HafalanOrder::BACKWARD): mixed
    {
        if ($records->isEmpty()) {
            return null;
        }

        return $records->sort(function ($a, $b) use ($direction) {
            $dateA = $a->submitted_at ? Carbon::parse($a->submitted_at)->timestamp : 0;
            $dateB = $b->submitted_at ? Carbon::parse($b->submitted_at)->timestamp : 0;
            if ($dateA !== $dateB) {
                return $dateB <=> $dateA;
            }

            $rankA = HafalanOrder::rank((int) ($a->surah?->number ?? 114), (int) ($a->ayah_end ?? 0), $direction);
            $rankB = HafalanOrder::rank((int) ($b->surah?->number ?? 114), (int) ($b->ayah_end ?? 0), $direction);

            return $rankB <=> $rankA;
        })->first();
    }

    /**
     * Cari capaian terjauh dari kumpulan setoran, dengan aturan khusus Kelas 10 / Metode
     * Ummi: Ziyadah dimulai dari Juz 30 (Surah 114 An-Naas mundur ke 78 An-Naba'), jadi
     * capaian terjauh adalah nomor surah TERKECIL di rentang itu -- kebalikan dari urutan
     * mushaf normal yang dipakai kelas 11 & 12. Di luar Juz 30, tetap pakai urutan normal.
     *
     * @param  Collection<int, mixed>  $records
     */
    public function furthestRecord(Collection $records, bool $isGrade10Ummi = false, ?string $direction = HafalanOrder::BACKWARD): mixed
    {
        if ($records->isEmpty()) {
            return null;
        }

        if ($isGrade10Ummi) {
            $juz30Records = $records->filter(fn ($r) => ($r->surah?->number ?? 0) >= 78 && ($r->surah?->number ?? 0) <= 114);

            if ($juz30Records->isNotEmpty()) {
                return $juz30Records->sort(function ($a, $b) {
                    $numA = $a->surah?->number ?? 114;
                    $numB = $b->surah?->number ?? 114;
                    if ($numA !== $numB) {
                        return $numA <=> $numB;
                    }
                    $dateA = $a->submitted_at ? Carbon::parse($a->submitted_at)->timestamp : 0;
                    $dateB = $b->submitted_at ? Carbon::parse($b->submitted_at)->timestamp : 0;

                    return $dateB <=> $dateA;
                })->first();
            }
        }

        return $this->latestByPosition($records, $direction);
    }
}
