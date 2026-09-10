<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TahfizhLevelAndUmmiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;

    private TeacherProfile $teacher;

    private Student $studentUmmi;

    private Student $studentReguler;

    private Surah $surah;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);

        // Create teacher
        $this->teacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        $this->teacher = TeacherProfile::create([
            'user_id' => $this->teacherUser->id,
            'employee_number' => 'T-001',
            'phone' => '081111111111',
        ]);

        // Create Programs & ClassRooms
        $program = Program::create(['name' => 'Tahfizh Reguler', 'status' => 'active']);

        $classX = ClassRoom::create([
            'name' => 'Kelas X-A',
            'level' => '10',
            'program_id' => $program->id,
        ]);

        $classXI = ClassRoom::create([
            'name' => 'Kelas XI-A',
            'level' => '11',
            'program_id' => $program->id,
        ]);

        // Create Students
        $userUmmi = User::factory()->create(['role_id' => $studentRole->id]);
        $this->studentUmmi = Student::create([
            'user_id' => $userUmmi->id,
            'class_room_id' => $classX->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Kelas 10',
            'student_number' => 'S-001',
            'status' => 'active',
            'tahfizh_level' => 'ummi',
        ]);

        $userReguler = User::factory()->create(['role_id' => $studentRole->id]);
        $this->studentReguler = Student::create([
            'user_id' => $userReguler->id,
            'class_room_id' => $classXI->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Kelas 11',
            'student_number' => 'S-002',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        $this->surah = Surah::create([
            'number' => 1,
            'name_arabic' => 'الفاتحة',
            'name_latin' => 'Al-Fatihah',
            'total_ayah' => 7,
            'revelation_type' => 'meccan',
        ]);
    }

    public function test_auto_defaults_level_to_ummi_for_grade_10_classroom()
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $admin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $program = Program::create(['name' => 'Program A', 'status' => 'active']);
        $classX = ClassRoom::create([
            'name' => 'X IPA 1',
            'level' => '10',
            'program_id' => $program->id,
        ]);

        $studentUser = User::factory()->create(['role_id' => Role::where('name', 'student')->first()->id]);

        $response = $this->actingAs($admin)->post(route('students.store'), [
            'user_id' => $studentUser->id,
            'class_room_id' => $classX->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Baru X',
            'student_number' => 'S-X01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'name' => 'Santri Baru X',
            'tahfizh_level' => 'ummi',
        ]);
    }

    public function test_teacher_can_save_ummi_record_for_ummi_student()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('ummi-records.store'), [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 5,
            'tanggal' => now()->toDateString(),
            'hafalan_surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-5',
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman' => 'Halaman 12',
            'materi' => 'Mad Jaiz Munfashil',
            'nilai' => 'B+',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
            'keterangan' => 'Salah 1 kali pada mad munfashil.',
            'redirect_to' => 'hafalan',
        ]);

        $response->assertRedirect(route('hafalan-records.index', ['category' => 'ummi']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 5,
            'ummi_jilid' => 'Jilid 2',
            'nilai' => 'B+',
        ]);
    }

    public function test_teacher_can_save_multiple_ummi_hafalan_records_for_ummi_student()
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $response = $this->actingAs($this->teacherUser)->post(route('ummi-records.store'), [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 6,
            'tanggal' => now()->toDateString(),
            'hafalan_surah_ids' => [$this->surah->id, $surah2->id],
            'hafalan_ayahs' => ['1-7', '1-5'],
            'ummi_jilid' => 'Jilid 5',
            'ummi_halaman' => 'Halaman 1',
            'materi' => 'Materi Baru',
            'nilai' => 'A',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Ya',
            'keterangan' => 'Lancar jaya.',
            'redirect_to' => 'hafalan',
        ]);

        $response->assertRedirect(route('hafalan-records.index', ['category' => 'ummi']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 6,
            'hafalan_surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-7',
        ]);

        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 6,
            'hafalan_surah_id' => $surah2->id,
            'hafalan_ayah' => '1-5',
        ]);
    }

    public function test_can_update_tahfizh_target_term_in_student_report()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('digital-reports.update', $this->studentReguler), [
            'academic_year' => '2025/2026',
            'semester' => 1,
            'teacher_notes' => 'Catatan ulasan wali kelas.',
            'tahfizh_target_term' => 'Selesai Juz 29 di term ini',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_reports', [
            'student_id' => $this->studentReguler->id,
            'academic_year' => '2025/2026',
            'semester' => 1,
            'tahfizh_target_term' => 'Selesai Juz 29 di term ini',
        ]);
    }

    public function test_report_computes_correct_latest_achievement_for_ummi()
    {
        // Add Ummi Record
        UmmiRecord::create([
            'student_id' => $this->studentUmmi->id,
            'teacher_id' => $this->teacher->id,
            'tatap_muka' => 10,
            'tanggal' => now(),
            'ummi_jilid' => 'Jilid 3',
            'ummi_halaman' => '15',
            'materi' => 'Materi UMMI',
            'nilai' => 'A',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
            'keterangan' => 'Sangat lancar',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('digital-reports.show', $this->studentUmmi));
        $response->assertStatus(200);

        // Verify variables are passed in view
        $response->assertViewHas('tahfizhLevelLabel', 'Metode Ummi');
        $response->assertViewHas('latestCapaianText', 'Jilid 3 Hal. 15 [Nilai: A]');
        $response->assertViewHas('latestCapaianNotes', 'Sangat lancar');
    }

    public function test_report_computes_correct_latest_achievement_for_reguler()
    {
        // Add passed Hafalan Record
        HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 95,
            'status' => 'passed',
            'submitted_at' => now(),
            'notes' => 'Sangat baik',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('digital-reports.show', $this->studentReguler));
        $response->assertStatus(200);

        $response->assertViewHas('tahfizhLevelLabel', 'Reguler');
        $response->assertViewHas('latestCapaianText', 'QS. Al-Fatihah (Ayat 1-7)');
        $response->assertViewHas('latestCapaianNotes', 'Sangat baik');
    }

    public function test_progress_computes_correct_completed_juz()
    {
        $service = new StudentProgressService;

        // Seed Al-Fatihah ayahs with Juz 1 in the database
        // Delete any existing ayahs for surah 1 to make it clean
        DB::table('ayahs')->where('surah_id', $this->surah->id)->delete();
        for ($i = 1; $i <= 7; $i++) {
            DB::table('ayahs')->insert([
                'surah_id' => $this->surah->id,
                'ayah_number' => $i,
                'juz' => 1,
            ]);
        }

        // Add a second Surah and an ayah under Juz 1 so that Juz 1 has unmemorized ayahs
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);
        DB::table('ayahs')->insert([
            'surah_id' => $surah2->id,
            'ayah_number' => 1,
            'juz' => 1,
        ]);

        // Initially no juz completed
        $progress = $service->calculate($this->studentReguler);
        $this->assertEquals(0, $progress['completed_juz_count']);
        $this->assertEquals('Belum ada Juz lengkap', $progress['completed_juz_list']);

        // Set student to pass all 7 ayahs of Al-Fatihah
        HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 95,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        // Clear static cache in StudentProgressService to reload DB data
        $ref = new \ReflectionClass(StudentProgressService::class);
        $prop1 = $ref->getProperty('allAyahs');
        $prop1->setAccessible(true);
        $prop1->setValue(null, null);

        $prop2 = $ref->getProperty('juzTotalAyahs');
        $prop2->setAccessible(true);
        $prop2->setValue(null, null);

        // Now, since Al-Fatihah ayahs (which are all in Juz 1) are memorized,
        // Juz 1 is still not complete if there are other ayahs under Juz 1 in DB.
        $progress2 = $service->calculate($this->studentReguler);
        $this->assertEquals(0, $progress2['completed_juz_count']);

        // Let's create a custom Surah that represents all ayahs in Juz 30 for this test
        $dummySurah = Surah::create([
            'number' => 999,
            'name_arabic' => 'الزلزلة',
            'name_latin' => 'Az-Zalzalah',
            'total_ayah' => 3,
            'revelation_type' => 'meccan',
        ]);

        DB::table('ayahs')->where('surah_id', $dummySurah->id)->delete();
        for ($i = 1; $i <= 3; $i++) {
            DB::table('ayahs')->insert([
                'surah_id' => $dummySurah->id,
                'ayah_number' => $i,
                'juz' => 30,
            ]);
        }

        // Reset cache again
        $prop1->setValue(null, null);
        $prop2->setValue(null, null);

        // Complete 1 out of 3 ayahs for dummySurah
        HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $dummySurah->id,
            'ayah_start' => 1,
            'ayah_end' => 1,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $progress3 = $service->calculate($this->studentReguler);
        $this->assertEquals(0, $progress3['completed_juz_count']);

        // Complete remaining 2 ayahs
        HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $dummySurah->id,
            'ayah_start' => 2,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $progress4 = $service->calculate($this->studentReguler);
        $this->assertEquals(1, $progress4['completed_juz_count']);
        $this->assertEquals('Juz 30', $progress4['completed_juz_list']);
    }

    public function test_baris_manual_saving_and_lines_count_attributes()
    {
        // Store reguler record with manual baris count
        $record = HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'submitted_at' => now(),
            'baris' => 12.50,
        ]);

        $this->assertEquals(12.50, $record->lines_count);

        // Store ummi record with manual baris count
        $ummiRecord = UmmiRecord::create([
            'student_id' => $this->studentUmmi->id,
            'teacher_id' => $this->teacher->id,
            'tatap_muka' => 1,
            'tanggal' => now(),
            'hafalan_surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-7',
            'ummi_jilid' => 'Jilid 1',
            'nilai' => 'A',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
            'baris' => 5.25,
        ]);

        $this->assertEquals(5.25, $ummiRecord->lines_count);
    }

    public function test_ummi_category_filters_out_non_grade_10_classes()
    {
        // When visiting reguler category, all classes are visible
        $responseReguler = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index', ['category' => 'reguler']));
        $responseReguler->assertStatus(200);
        $classesReguler = $responseReguler->viewData('classRooms');
        $this->assertTrue($classesReguler->contains('name', 'Kelas X-A'));
        $this->assertTrue($classesReguler->contains('name', 'Kelas XI-A'));

        // When visiting ummi category, only Grade 10 classes are visible
        $responseUmmi = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index', ['category' => 'ummi']));
        $responseUmmi->assertStatus(200);
        $classesUmmi = $responseUmmi->viewData('classRooms');
        $this->assertTrue($classesUmmi->contains('name', 'Kelas X-A'));
        $this->assertFalse($classesUmmi->contains('name', 'Kelas XI-A'));
    }
}
