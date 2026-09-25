<?php

use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menghitung ulang tatap_muka semua ummi_records yang sudah ada supaya
     * mengikuti nomor pertemuan riil dari kalender hari efektif kelas
     * (AcademicCalendarService), bukan lagi urutan penyimpanan manual lama.
     *
     * Memakai class_room_id murid SAAT INI (bukan riwayat kelas di tanggal
     * setoran, yang tidak disimpan) sebagai pendekatan terbaik yang tersedia
     * -- murid yang sudah pindah kelas sejak tanggal itu tidak bisa dihitung
     * ulang secara akurat, tapi ini sangat jarang terjadi untuk data UMMI.
     */
    public function up(): void
    {
        $calendar = new AcademicCalendarService;

        $classRoomsById = ClassRoom::query()->with('program')->get()->keyBy('id');
        $studentClassMap = Student::query()->pluck('class_room_id', 'id');

        DB::table('ummi_records')
            ->orderBy('id')
            ->select('id', 'student_id', 'tanggal')
            ->cursor()
            ->each(function ($record) use ($calendar, $classRoomsById, $studentClassMap) {
                $classRoomId = $studentClassMap[$record->student_id] ?? null;
                $classRoom = $classRoomId ? $classRoomsById->get($classRoomId) : null;

                if (! $classRoom) {
                    return;
                }

                $newTatapMuka = $calendar->tatapMukaNumber($classRoom, Carbon::parse($record->tanggal), forUmmi: true);

                DB::table('ummi_records')->where('id', $record->id)->update([
                    'tatap_muka' => $newTatapMuka,
                ]);
            });
    }

    /**
     * Tidak bisa mengembalikan nilai tatap_muka lama secara akurat -- nilai
     * asli (urutan penyimpanan manual) tidak disimpan di tempat lain.
     */
    public function down(): void {}
};
