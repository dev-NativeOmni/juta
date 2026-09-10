<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HafalanTargetsConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $teacher;
    private TeacherProfile $teacherProfile;
    private Role $roleSuperAdmin;
    private Role $roleTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $this->roleTeacher = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);

        $this->superAdmin = User::factory()->create([
            'role_id' => $this->roleSuperAdmin->id,
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'role_id' => $this->roleTeacher->id,
            'status' => 'active',
        ]);

        $this->teacherProfile = TeacherProfile::create([
            'user_id' => $this->teacher->id,
            'nip' => '1234567890',
        ]);
    }

    #[Test]
    public function super_admin_can_access_hafalan_targets_settings_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('settings.hafalan-targets'));
        $response->assertStatus(200);
        $response->assertSee('Target Progres Hafalan');
        $response->assertSee('Kelas 10 (Fase E)');
    }

    #[Test]
    public function non_authorized_users_cannot_access_hafalan_targets_settings(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('settings.hafalan-targets'));
        $response->assertStatus(403);
    }

    #[Test]
    public function super_admin_can_update_and_reset_hafalan_targets_config(): void
    {
        $payload = [
            'targets' => [
                'grade_10' => [
                    'tahfizh' => [
                        'target_juz_count' => 4,
                        'mode' => 'specific',
                        'specific_juz' => [30, 29, 28, 1],
                    ],
                    'reguler' => [
                        'target_juz_count' => 2,
                        'mode' => 'specific',
                        'specific_juz' => [30, 29],
                    ],
                ],
                'grade_11' => [
                    'tahfizh' => [
                        'target_juz_count' => 4,
                        'mode' => 'any',
                        'specific_juz' => [],
                    ],
                    'reguler' => [
                        'target_juz_count' => 2,
                        'mode' => 'any',
                        'specific_juz' => [],
                    ],
                ],
                'grade_12' => [
                    'tahfizh' => [
                        'target_juz_count' => 4,
                        'mode' => 'any',
                        'specific_juz' => [],
                    ],
                    'reguler' => [
                        'target_juz_count' => 2,
                        'mode' => 'any',
                        'specific_juz' => [],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('settings.hafalan-targets.update'), $payload);
        $response->assertRedirect(route('settings.hafalan-targets'));
        $response->assertSessionHas('success');

        $config = Setting::getHafalanTargetsConfig();
        $this->assertEquals(4, $config['grade_10']['tahfizh']['target_juz_count']);
        $this->assertEquals([30, 29, 28, 1], $config['grade_10']['tahfizh']['specific_juz']);

        // Test Reset
        $resetResponse = $this->actingAs($this->superAdmin)->post(route('settings.hafalan-targets.reset'));
        $resetResponse->assertRedirect(route('settings.hafalan-targets'));
        $resetResponse->assertSessionHas('success');
    }

    #[Test]
    public function grade_10_tahfizh_student_progress_is_calculated_against_4_specific_juz(): void
    {
        $programTahfizh = Program::create([
            'name' => 'Program Tahfizh',
            'meeting_frequency' => 'setiap hari',
        ]);

        $classRoom10Tahfizh = ClassRoom::create([
            'name' => 'X E1 (Tahfizh)',
            'level' => '10',
            'program_id' => $programTahfizh->id,
        ]);

        $student = Student::create([
            'name' => 'Santri Test',
            'student_number' => '1001',
            'class_room_id' => $classRoom10Tahfizh->id,
            'teacher_id' => $this->teacherProfile->id,
            'tahfizh_level' => 'tahfizh',
            'status' => 'active',
        ]);

        // Surah An-Naba' (Surah 78, Juz 30, total 40 ayat)
        $surahNaba = Surah::create([
            'number' => 78,
            'name_ar' => 'النبأ',
            'name_latin' => 'An-Naba\'',
            'total_ayah' => 40,
            'juz_start' => 30,
            'juz_end' => 30,
        ]);

        // Santri menyetor 40 ayat di Juz 30
        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $surahNaba->id,
            'ayah_start' => 1,
            'ayah_end' => 40,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $service = app(StudentProgressService::class);
        $progress = $service->calculate($student);

        // Target Grade 10 Tahfizh: Juz 30 (564) + 29 (431) + 28 (137) + 1 (148) = 1,280 ayat
        $this->assertEquals(1280, $progress['target_total_ayahs']);
        $this->assertEquals(40, $progress['memorized_ayahs']);
        $this->assertEquals(4, $progress['target_juz_count']);
        $this->assertEquals('specific', $progress['target_mode']);
        $this->assertEquals([30, 29, 28, 1], $progress['target_specific_juz']);

        // Expected percent: (40 / 1280) * 100 = 3.13%
        $this->assertEquals(3.13, $progress['progress_percent']);
        $this->assertEquals(1240, $progress['remaining_ayahs']);
    }

    #[Test]
    public function grade_10_reguler_student_progress_is_calculated_against_2_specific_juz(): void
    {
        $programReguler = Program::create([
            'name' => 'Program Reguler',
            'meeting_frequency' => 'seminggu sekali',
        ]);

        $classRoom10Reguler = ClassRoom::create([
            'name' => 'X E2 (Reguler)',
            'level' => '10',
            'program_id' => $programReguler->id,
        ]);

        $student = Student::create([
            'name' => 'Santri Reguler Test',
            'student_number' => '1002',
            'class_room_id' => $classRoom10Reguler->id,
            'teacher_id' => $this->teacherProfile->id,
            'tahfizh_level' => 'reguler',
            'status' => 'active',
        ]);

        // Surah An-Naba' (Surah 78, Juz 30, total 40 ayat)
        $surahNaba = Surah::create([
            'number' => 78,
            'name_ar' => 'النبأ',
            'name_latin' => 'An-Naba\'',
            'total_ayah' => 40,
            'juz_start' => 30,
            'juz_end' => 30,
        ]);

        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $surahNaba->id,
            'ayah_start' => 1,
            'ayah_end' => 40,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $service = app(StudentProgressService::class);
        $progress = $service->calculate($student);

        // Target Grade 10 Reguler: Juz 30 (564) + 29 (431) = 995 ayat
        $this->assertEquals(995, $progress['target_total_ayahs']);
        $this->assertEquals(40, $progress['memorized_ayahs']);
        $this->assertEquals(2, $progress['target_juz_count']);
        $this->assertEquals([30, 29], $progress['target_specific_juz']);

        // Expected percent: (40 / 995) * 100 = 4.02%
        $this->assertEquals(4.02, $progress['progress_percent']);
        $this->assertEquals(955, $progress['remaining_ayahs']);
    }
}
