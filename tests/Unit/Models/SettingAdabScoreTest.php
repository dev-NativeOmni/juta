<?php

namespace Tests\Unit\Models;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class SettingAdabScoreTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function batch_calculation_matches_individual_calculation_for_each_student(): void
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $secondStudent = Student::create([
            'user_id' => null,
            'class_room_id' => $this->student->class_room_id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Santri Kedua',
            'student_number' => 'TEST-SNT-002',
            'gender' => 'male',
            'birth_date' => '2010-06-15',
            'status' => 'active',
        ]);

        $thirdStudent = Student::create([
            'user_id' => null,
            'class_room_id' => $this->student->class_room_id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Santri Ketiga (Tanpa Data)',
            'student_number' => 'TEST-SNT-003',
            'gender' => 'female',
            'birth_date' => '2010-07-20',
            'status' => 'active',
        ]);

        AdabRecord::create([
            'student_id' => $this->student->id,
            'evaluator_id' => $this->student->id,
            'assessment_date' => now()->startOfMonth()->addDays(2),
            'total_score' => 100,
        ]);

        AdabRecord::create([
            'student_id' => $this->student->id,
            'evaluator_id' => $this->student->id,
            'assessment_date' => now()->startOfMonth()->addDays(5),
            'total_score' => 100,
        ]);

        AdabRecord::create([
            'student_id' => $secondStudent->id,
            'evaluator_id' => $secondStudent->id,
            'assessment_date' => now()->startOfMonth()->addDays(1),
            'total_score' => 100,
        ]);

        AdabMentorAssessment::create([
            'student_id' => $this->student->id,
            'mentor_id' => $this->teacherUser->id,
            'year' => $year,
            'month' => $month,
            'mentor_score' => 88,
        ]);

        // Older assessment only, no current-month row: must fall back to it.
        AdabMentorAssessment::create([
            'student_id' => $secondStudent->id,
            'mentor_id' => $this->teacherUser->id,
            'year' => $year - 1,
            'month' => 6,
            'mentor_score' => 70,
        ]);

        $studentIds = [$this->student->id, $secondStudent->id, $thirdStudent->id];

        $expected = collect($studentIds)
            ->mapWithKeys(fn (int $id) => [$id => Setting::calculateAdabScore($id, $year, $month)])
            ->all();

        // Clear the static memoization the individual calls above just warmed,
        // so the batch method below hits the database fresh.
        $reflection = new \ReflectionClass(Setting::class);
        $property = $reflection->getProperty('studentAdabScoreCache');
        $property->setAccessible(true);
        $property->setValue(null, []);

        DB::enableQueryLog();
        $batch = Setting::calculateAdabScoresForStudents($studentIds, $year, $month);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals($expected, $batch);

        // The whole point: a handful of queries regardless of student count,
        // not 2-3 per student.
        $this->assertLessThanOrEqual(4, $queryCount);
    }
}
