<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kalender sekolah terpadu (lihat App\Services\SchoolCalendar). Menggantikan setting JSON
 * per tahun 'national_holidays_{Y}' (Libur Total) & 'class_holidays_{Y}' (Libur Sebagian).
 *
 * - class_room_id NULL  : berlaku untuk semua kelas; tahfizh_off/adab_off menentukan cakupan.
 * - class_room_id terisi: libur Tahfizh khusus kelas itu (Libur Sebagian); Adab tidak terpengaruh.
 *
 * Setting lama tidak dihapus supaya rollback aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('class_room_id')->nullable()->constrained('class_rooms')->cascadeOnDelete();
            $table->boolean('tahfizh_off')->default(false);
            $table->boolean('adab_off')->default(false);
            $table->string('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'class_room_id']);
        });

        $now = now();
        $classIds = DB::table('class_rooms')->pluck('id')->all();
        $rows = [];

        foreach (DB::table('settings')->where('key', 'like', 'national_holidays_%')->get() as $setting) {
            foreach ((array) json_decode((string) $setting->value, true) as $date) {
                if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $rows["{$date}|"] = [
                        'date' => $date, 'class_room_id' => null, 'tahfizh_off' => true, 'adab_off' => true,
                        'note' => null, 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (DB::table('settings')->where('key', 'like', 'class_holidays_%')->get() as $setting) {
            foreach ((array) json_decode((string) $setting->value, true) as $date => $ids) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                    continue;
                }
                foreach (array_unique(array_map('intval', (array) $ids)) as $classId) {
                    if (in_array($classId, $classIds, true)) {
                        $rows["{$date}|{$classId}"] = [
                            'date' => $date, 'class_room_id' => $classId, 'tahfizh_off' => true, 'adab_off' => false,
                            'note' => null, 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('calendar_days')->insert($chunk);
        }

        // Tahun yang punya setting lama (walau kosong) sudah "diatur admin" -- jangan pakai default lagi.
        $configuredYears = DB::table('settings')->where('key', 'like', 'national_holidays_%')->pluck('key')
            ->map(fn ($key) => (int) substr($key, strlen('national_holidays_')))
            ->filter()
            ->values();
        foreach ($configuredYears as $year) {
            DB::table('settings')->updateOrInsert(['key' => "calendar_configured_{$year}"], ['value' => '1', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'like', 'calendar_configured_%')->delete();
        Schema::dropIfExists('calendar_days');
    }
};
