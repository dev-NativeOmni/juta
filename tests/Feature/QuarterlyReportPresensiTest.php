<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Student;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Regresi untuk bug: kolom presensi mingguan di Laporan Triwulan (Halaqoh)
 * selalu menampilkan "Hadir" walau tidak ada pertemuan nyata di pekan itu
 * (bug ternary `$hasSetoran ? 'Hadir' : 'Hadir'`), dan target baris bulanan
 * memakai pengali pertemuan tetap (4 atau 20) alih-alih jumlah pertemuan
 * terjadwal bulan tsb menurut kalender & jadwal kelas, sehingga capaian vs target tidak sinkron.
 */
class QuarterlyReportPresensiTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function weekly_presensi_only_marks_hadir_on_weeks_with_real_attendance_or_setoran(): void
    {
        $program = Program::create([
            'name' => 'Program Qiroati Reguler',
            'status' => 'active',
        ]);

        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Reguler Test',
            'level' => 'Menengah',
            'tahfizh_days' => [3], // Rabu: 2, 9, 16, 23, 30 September 2026
        ]);

        // 9 September (Rabu, pekan 2) libur nasional -> tidak ada pertemuan aktif di pekan itu.
        $this->markHoliday('2026-09-09');

        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
        ]);

        // Hanya 2 pertemuan nyata bulan ini: 24 Sept (pekan 4) & 29 Sept (pekan 5).
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-24',
            'status' => 'hadir',
        ]);
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-29',
            'status' => 'hadir',
        ]);

        foreach (['2026-09-24', '2026-09-29'] as $date) {
            $record = HafalanRecord::create([
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacherProfile->id,
                'submitted_at' => $date,
            ]);
            $record->surahs()->create([
                'surah_id' => $this->surah->id,
                'ayah_start' => 1,
                'ayah_end' => 5,
                'submission_type' => 'new',
                'status' => 'passed',
                'score' => 90,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('Pilih Bulan Laporan');
        $response->assertSee('Belum di input');
        $response->assertSee('Libur');
        $response->assertDontSee('Belum Ada');

        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();

            // Seluruh bulan dalam term (Jul-Sep) tampil sekaligus.
            if (array_keys($halaqah['monthly']) !== ['07', '08', '09']) {
                return false;
            }

            $september = $halaqah['monthly']['09'];
            $pekan = $september['presensi'][$this->student->id]['pekan'];

            // Pekan 1-3: tidak ada presensi maupun setoran nyata -> jangan "Hadir".
            foreach ([1, 2, 3] as $p) {
                if ($pekan[$p] === 'Hadir') {
                    return false;
                }
            }

            // Pekan 2 tidak punya hari efektif (libur), pekan 1 & 3 punya hari efektif
            // tapi belum ada yang diinput musyrif.
            if ($pekan[2] !== 'Libur' || $pekan[1] !== 'Belum di input' || $pekan[3] !== 'Belum di input') {
                return false;
            }

            // Pekan 4 & 5: ada presensi/setoran nyata -> harus "Hadir".
            if ($pekan[4] !== 'Hadir' || $pekan[5] !== 'Hadir') {
                return false;
            }

            // Target baris = level (reguler=5) x jumlah pertemuan terjadwal bulan itu
            // (Rabu 2, 16, 23, 30 September; 9 September libur) = 4, walau hanya
            // 2 pertemuan yang sudah diinput musyrif.
            $regulerRow = collect($september['reguler_records'])
                ->firstWhere('student_id', $this->student->id);

            if ($regulerRow['target_lines'] !== 20) {
                return false;
            }

            // Juli & Agustus belum ada input sama sekali, tapi tetap punya target dari
            // kalender: Rabu Juli = 5, Agustus = 4 (masing-masing x 5 baris).
            if ($halaqah['monthly']['07']['reguler_records'][0]['target_lines'] !== 25
                || $halaqah['monthly']['08']['reguler_records'][0]['target_lines'] !== 20) {
                return false;
            }

            // Rekap term menjumlahkan target semua bulan.
            $termRow = collect($halaqah['term_records'])->firstWhere('student_id', $this->student->id);

            return $termRow['target_lines'] === 65 && $termRow['total_lines'] > 0;
        });
    }

    #[Test]
    public function tahfizh_program_shows_every_month_of_the_term_at_once(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        // Selasa 7 Juli libur nasional.
        $this->markHoliday('2026-07-07');

        // Setoran di Juli (07-06) dan September (09-08); Agustus kosong.
        foreach (['2026-07-06', '2026-09-08'] as $date) {
            Attendance::create([
                'student_id' => $this->student->id,
                'class_room_id' => $classRoom->id,
                'teacher_id' => $this->teacherProfile->id,
                'tanggal' => $date,
                'status' => 'hadir',
            ]);
            $record = HafalanRecord::create([
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacherProfile->id,
                'submitted_at' => $date,
            ]);
            $record->surahs()->create([
                'surah_id' => $this->surah->id,
                'ayah_start' => 1,
                'ayah_end' => 5,
                'submission_type' => 'new',
                'status' => 'passed',
                'score' => 90,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Juli');
        $response->assertSee('Agustus');
        $response->assertSee('September');

        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();
            $studentId = $this->student->id;

            $monthLines = fn ($code) => collect($halaqah['monthly'][$code]['tahfizh_records'])
                ->firstWhere('student_id', $studentId)['total_lines'];

            $julyDays = collect($halaqah['monthly']['07']['tahfizh_records'])
                ->firstWhere('student_id', $studentId)['pekan'][1]['days'];

            return array_keys($halaqah['monthly']) === ['07', '08', '09']
                && $julyDays['Selasa']['surah'] === 'Libur'
                && $julyDays['Rabu']['surah'] === 'Belum di input'
                && array_keys($halaqah['presensi'][$studentId]) === ['Juli', 'Agustus', 'September']
                && $monthLines('07') > 0
                && $monthLines('08') == 0
                && $monthLines('09') > 0
                && collect($halaqah['term_records'])->firstWhere('student_id', $studentId)['total_lines']
                    == $monthLines('07') + $monthLines('09');
        });
    }

    #[Test]
    public function term_target_is_filled_automatically_for_grade_11_12_but_not_grade_10(): void
    {
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $baqarah = Surah::firstOrCreate(
            ['number' => 2],
            ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]
        );

        // Kelas 12: target semester otomatis. 14 Rabu di Jul-Sep 2026 x 5 baris = 70 baris.
        $class12 = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F3',
            'level' => 'XII',
            'tahfizh_days' => [3],
        ]);
        $this->student->update(['class_room_id' => $class12->id, 'tahfizh_level' => 'reguler']);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-08',
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);

        $query = ['academic_year' => '2026/2027', 'term' => '1'];

        $termRow = fn ($classId) => collect(
            collect($this->actingAs($this->admin)
                ->get(route('reports.quarterly', $query + ['class_room_id' => $classId]))
                ->viewData('halaqahData'))->first()['term_records']
        )->firstWhere('student_id', $this->student->id);

        $row12 = $termRow($class12->id);
        $this->assertSame(70, $row12['target_lines']);
        // Al-Fatihah (7 baris) habis, sisanya berjalan ke Al-Baqarah dari ayat 1.
        $this->assertSame('Al-Baqarah', $row12['target_surah']);
        $this->assertStringStartsWith('1 - ', $row12['target_ayat']);

        // Kelas 10 tetap memakai Target Hafalan/Ummi yang dibuat guru, tanpa target otomatis.
        $class10 = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
            'tahfizh_days' => [3],
        ]);
        $student10 = Student::create([
            'class_room_id' => $class10->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas 10',
            'student_number' => 'TEST-SNT-010',
            'gender' => 'male',
            'birth_date' => '2010-05-10',
            'status' => 'active',
            'tahfizh_level' => 'ummi',
        ]);
        $record10 = HafalanRecord::create([
            'student_id' => $student10->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-08',
        ]);
        $record10->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);
        HafalanTarget::create([
            'student_id' => $student10->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => '2026-09-15',
            'status' => 'active',
        ]);

        $row10 = collect(collect($this->actingAs($this->admin)
            ->get(route('reports.quarterly', $query + ['class_room_id' => $class10->id]))
            ->viewData('halaqahData'))->first()['term_records'])->firstWhere('student_id', $student10->id);

        $this->assertSame('Al-Fatihah', $row10['target_surah']);
        $this->assertSame('1 - 5', $row10['target_ayat']);
        $this->assertSame(0, HafalanTarget::query()->where('student_id', $student10->id)->whereNotNull('auto_month')->count());
    }

    #[Test]
    public function capaian_uses_the_furthest_surah_when_two_are_submitted_on_the_same_date(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F4 Test',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $alQamar = Surah::firstOrCreate(
            ['number' => 54],
            ['name_ar' => 'القمر', 'name_latin' => 'Al-Qamar', 'total_ayah' => 55, 'juz_start' => 27, 'juz_end' => 27]
        );
        $arRahman = Surah::firstOrCreate(
            ['number' => 55],
            ['name_ar' => 'الرحمن', 'name_latin' => 'Ar-Rahman', 'total_ayah' => 78, 'juz_start' => 27, 'juz_end' => 27]
        );

        // Satu sesi setoran (satu tanggal): musyrif input Al-Qamar dulu (menyelesaikannya),
        // baru menambahkan Ar-Rahman lewat "+Tambah Surat" -- Ar-Rahman lebih jauh di mushaf
        // dan seharusnya jadi capaian terakhir, bukan Al-Qamar walau di-input lebih dulu.
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-10',
        ]);
        $record->surahs()->create([
            'surah_id' => $alQamar->id,
            'ayah_start' => 50,
            'ayah_end' => 55,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);
        $record->surahs()->create([
            'surah_id' => $arRahman->id,
            'ayah_start' => 1,
            'ayah_end' => 4,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();
            $termRow = collect($halaqah['term_records'])->firstWhere('student_id', $this->student->id);
            $septRow = collect($halaqah['monthly']['09']['reguler_records'])->firstWhere('student_id', $this->student->id);

            return $termRow['capaian_surah'] === 'Ar-Rahman'
                && $termRow['capaian_ayat'] === '1-4'
                && $septRow['capaian_surah'] === 'Ar-Rahman'
                && $septRow['capaian_ayat'] === '1-4';
        });
    }
}
