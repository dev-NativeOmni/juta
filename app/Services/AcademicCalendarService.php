<?php

namespace App\Services;

use App\Models\ClassRoom;
use Carbon\Carbon;

/**
 * Menghitung nomor Tatap Muka (TM) berdasarkan kalender hari efektif
 * sebuah kelas: mengikuti jadwal hari kelas (tahfizh_days), frekuensi
 * pertemuan program (harian vs seminggu sekali), serta melewati tanggal
 * libur nasional dan libur khusus per-kelas.
 */
class AcademicCalendarService
{
    /**
     * Hari efektif UMMI: Senin-Kamis saja. Jumat tetap hari aktif kelas
     * (tahfizh_days) untuk tahfizh mandiri, tapi bukan bagian UMMI --
     * jadi tidak menambah hitungan TM UMMI walau kelasnya aktif hari itu.
     */
    private const UMMI_DAYS = [1, 2, 3, 4];

    /**
     * Cache per request: hasil scheduledMeetings() sama untuk seluruh murid satu kelas
     * di rentang tanggal yang sama, jadi tidak perlu dihitung ulang per murid.
     *
     * @var array<string, int>
     */
    private static array $scheduledMeetingsCache = [];

    /**
     * Tanggal mulai term (semester) yang memuat tanggal ini.
     * Term 1: Juli-September, Term 2: Oktober-Desember,
     * Term 3: Januari-Maret, Term 4: April-Juni.
     */
    public function termStartDate(Carbon $date): Carbon
    {
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        $termStartMonth = match (true) {
            $month >= 7 && $month <= 9 => 7,
            $month >= 10 && $month <= 12 => 10,
            $month >= 1 && $month <= 3 => 1,
            default => 4,
        };

        return Carbon::createFromDate($year, $termStartMonth, 1)->startOfDay();
    }

    /**
     * Apakah tanggal ini hari efektif untuk kelas: sesuai hari kelas
     * (tahfizh_days), bukan libur nasional, dan bukan libur khusus kelas
     * ini ("libur sebagian"). Untuk UMMI ($forUmmi), hari kelas dipersempit
     * ke Senin-Kamis saja, terlepas dari tahfizh_days kelasnya.
     */
    public function isEffectiveDay(ClassRoom $classRoom, Carbon $date, bool $forUmmi = false): bool
    {
        return app(SchoolCalendar::class)->isTahfizhEffectiveDay($classRoom, $date, $forUmmi ? self::UMMI_DAYS : []);
    }

    /**
     * Nomor Tatap Muka ke berapa suatu tanggal, dihitung dari hari efektif
     * pertama term yang memuat tanggal tsb sampai tanggal ini (inklusif).
     * Tanggal yang diberikan selalu dihitung sebagai satu pertemuan (karena
     * memang sedang dicatat setoran di tanggal itu), sekalipun jatuh di luar
     * hari efektif normal (mis. pertemuan susulan). Program dengan frekuensi
     * "seminggu sekali" hanya menghitung maksimal satu pertemuan per pekan
     * kalender. Untuk UMMI ($forUmmi), hanya Senin-Kamis yang dihitung --
     * Jumat dipakai untuk tahfizh mandiri dan tidak termasuk UMMI, sekalipun
     * kelasnya tetap aktif hari itu (tahfizh_days).
     */
    public function tatapMukaNumber(ClassRoom $classRoom, Carbon $date, bool $forUmmi = false): int
    {
        $isWeekly = $classRoom->program?->meeting_frequency === 'seminggu sekali';
        $targetDateString = $date->toDateString();

        $count = 0;
        $countedWeeks = [];
        $cursor = $this->termStartDate($date);

        while ($cursor->lte($date)) {
            $isTarget = $cursor->toDateString() === $targetDateString;

            if ($isTarget || $this->isEffectiveDay($classRoom, $cursor, $forUmmi)) {
                if ($isWeekly) {
                    $weekKey = $cursor->format('o-W');
                    if (! isset($countedWeeks[$weekKey])) {
                        $countedWeeks[$weekKey] = true;
                        $count++;
                    }
                } else {
                    $count++;
                }
            }

            $cursor = $cursor->copy()->addDay();
        }

        return $count;
    }

    /**
     * Rentang 3 bulan (Y-m => start/end) term yang memuat tanggal ini, dipakai bersama
     * termStartDate() untuk memecah satu term ke bulan-bulannya.
     *
     * @return array<string, array{start: Carbon, end: Carbon}>
     */
    public function termMonths(Carbon $date): array
    {
        $termStart = $this->termStartDate($date);
        $months = [];

        for ($i = 0; $i < 3; $i++) {
            $start = $termStart->copy()->addMonthsNoOverflow($i)->startOfMonth();
            $months[$start->format('Y-m')] = [
                'start' => $start,
                'end' => $start->copy()->endOfMonth()->startOfDay(),
            ];
        }

        return $months;
    }

    /**
     * Jumlah pertemuan terjadwal kelas dalam rentang tanggal (inklusif) menurut kalender:
     * hari kelas, libur nasional, dan libur khusus kelas. Program "seminggu sekali"
     * dihitung maksimal satu pertemuan per pekan kalender.
     */
    public function scheduledMeetings(ClassRoom $classRoom, Carbon $start, Carbon $end): int
    {
        $cacheKey = $classRoom->id.'|'.$start->toDateString().'|'.$end->toDateString();

        if (isset(self::$scheduledMeetingsCache[$cacheKey])) {
            return self::$scheduledMeetingsCache[$cacheKey];
        }

        $isWeekly = $classRoom->program?->meeting_frequency === 'seminggu sekali';
        $count = 0;
        $countedWeeks = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if ($this->isEffectiveDay($classRoom, $cursor)) {
                if ($isWeekly) {
                    $weekKey = $cursor->format('o-W');
                    if (! isset($countedWeeks[$weekKey])) {
                        $countedWeeks[$weekKey] = true;
                        $count++;
                    }
                } else {
                    $count++;
                }
            }

            $cursor = $cursor->copy()->addDay();
        }

        return self::$scheduledMeetingsCache[$cacheKey] = $count;
    }
}
