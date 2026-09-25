<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Laporan Triwulan untuk guru memakai halaman pratinjau yang sama dengan admin,
 * tetapi dibatasi ke kelas & murid yang dia ampu -- termasuk saat export per kelas.
 */
class QuarterlyReportTeacherPreviewTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classA;

    private ClassRoom $classOther;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $program = Program::create(['name' => 'Program Reguler Preview', 'status' => 'active']);
        $this->classA = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Preview A', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->classOther = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Preview Lain', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $this->student->update(['class_room_id' => $this->classA->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid Saya']);

        $otherUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->firstOrFail()->id,
            'name' => 'Ust. Lain',
            'status' => 'active',
        ]);
        $otherProfile = TeacherProfile::create(['user_id' => $otherUser->id, 'employee_number' => 'TEST-GURU-PRV']);

        // Murid guru lain di kelas yang SAMA, dan di kelas yang tidak diampu guru ini.
        foreach ([[$this->classA, 'Murid Guru Lain', 'PRV-1'], [$this->classOther, 'Murid Kelas Lain', 'PRV-2']] as [$class, $name, $number]) {
            Student::create([
                'class_room_id' => $class->id,
                'teacher_id' => $otherProfile->id,
                'name' => $name,
                'student_number' => $number,
                'gender' => 'male',
                'birth_date' => '2008-01-01',
                'status' => 'active',
                'tahfizh_level' => 'reguler',
            ]);
        }
    }

    #[Test]
    public function teacher_preview_only_lists_own_classes_and_own_students(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('reports.quarterly', ['class_room_id' => $this->classA->id]));

        $response->assertOk();
        $response->assertViewIs('reports.quarterly');
        $this->assertSame([$this->classA->id], $response->viewData('classRooms')->pluck('id')->all());
        $response->assertSee('Murid Saya');
        $response->assertDontSee('Murid Guru Lain');
        $response->assertDontSee('Murid Kelas Lain');
        $response->assertDontSee('XI Preview Lain');
    }

    #[Test]
    public function teacher_cannot_open_a_class_they_do_not_teach_via_the_url(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('reports.quarterly', ['class_room_id' => $this->classOther->id]));

        $response->assertOk();
        $this->assertSame($this->classA->id, $response->viewData('selectedClass')->id);
        $response->assertDontSee('Murid Kelas Lain');
    }

    #[Test]
    public function teacher_class_export_is_limited_to_own_students(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('reports.quarterly.export', ['class_room_id' => $this->classA->id]));

        $response->assertOk();
        $tmpPath = tempnam(sys_get_temp_dir(), 'qrtp').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());
        $cells = collect(\PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath)->getAllSheets())
            ->flatMap(fn ($sheet) => collect($sheet->toArray())->flatten())
            ->filter()
            ->map(fn ($v) => (string) $v);
        unlink($tmpPath);

        $this->assertTrue($cells->contains('Murid Saya'));
        $this->assertFalse($cells->contains('Murid Guru Lain'));
        $this->assertFalse($cells->contains('Murid Kelas Lain'));
    }

    #[Test]
    public function teacher_without_classes_sees_an_empty_state_not_other_students(): void
    {
        $this->student->update(['teacher_id' => null]);

        $response = $this->actingAs($this->teacherUser)->get(route('reports.quarterly'));

        $response->assertOk();
        $response->assertSee('Belum ada murid aktif yang terhubung ke akun Anda');
        $response->assertDontSee('Murid Guru Lain');
        $response->assertDontSee('Murid Kelas Lain');
    }

    #[Test]
    public function admin_still_sees_every_student_in_the_class(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', ['class_room_id' => $this->classA->id]));

        $response->assertOk();
        $response->assertSee('Murid Saya');
        $response->assertSee('Murid Guru Lain');
    }
}
