<?php

namespace Tests;

use App\Models\CalendarDay;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tandai tanggal sebagai libur untuk semua kelas (default: Libur Total).
     */
    protected function markHoliday(string $date, bool $tahfizhOff = true, bool $adabOff = true): void
    {
        CalendarDay::updateOrCreate(
            ['date' => $date, 'class_room_id' => null],
            ['tahfizh_off' => $tahfizhOff, 'adab_off' => $adabOff]
        );
    }

    /**
     * Libur Sebagian: Tahfizh libur hanya untuk kelas-kelas ini.
     */
    protected function markClassHoliday(string $date, int ...$classRoomIds): void
    {
        foreach ($classRoomIds as $classRoomId) {
            CalendarDay::updateOrCreate(
                ['date' => $date, 'class_room_id' => $classRoomId],
                ['tahfizh_off' => true, 'adab_off' => false]
            );
        }
    }
}
