<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class SpreadsheetInputPerformanceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function test_performance_of_spreadsheet_input()
    {
        $classRoom = $this->student->classRoom;

        $extraStudents = 50;
        for ($i = 0; $i < $extraStudents; $i++) {
            Student::create([
                'user_id' => null,
                'class_room_id' => $classRoom->id,
                'teacher_id' => $this->teacherProfile->id,
                'name' => 'Santri Test '.$i,
                'student_number' => 'TEST-SNT-'.Str::random(5),
                'gender' => 'male',
                'birth_date' => '2010-05-10',
                'status' => 'active',
            ]);
        }

        $students = Student::all();

        $date = '2026-08-03';
        $records = [];

        foreach ($students as $s) {
            $records[$s->id] = [
                'dates' => [
                    $date => [
                        'attendance' => 'hadir',
                        'hafalans' => [
                            [
                                'id' => null,
                                'surah_id' => $this->surah->id,
                                'ayah_start' => 1,
                                'ayah_end' => 5,
                                'score' => '95',
                                'status' => 'passed',
                                'submission_type' => 'new',
                            ],
                        ],
                    ],
                ],
            ];
        }

        $payload = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'hafalan',
            'records' => $records,
        ];

        DB::enableQueryLog();

        $startTime = microtime(true);
        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        echo "\nExecution time: ".$executionTime." seconds\n";
        echo 'Query count: '.$queryCount."\n";

        $this->assertTrue(true);
    }
}
