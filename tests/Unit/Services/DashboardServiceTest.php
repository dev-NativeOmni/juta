<?php

namespace Tests\Unit\Services;

use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->service = $this->app->make(DashboardService::class);
    }

    #[Test]
    public function it_returns_correct_stats_for_existing_teacher_with_records(): void
    {
        $teacherUser = $this->teacherUser;
        $student = $this->student;

        // Create some Hafalan and Murajaah records for today.
        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        MurajaahRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 4,
            'ayah_end' => 7,
            'status' => 'passed',
            'reviewed_at' => now(),
        ]);

        // Repeat/Needs improvement to test needs_attention
        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'repeat',
            'submitted_at' => now()->subDay(),
        ]);

        $stats = $this->service->teacherStats($teacherUser);

        $this->assertEquals($this->teacherProfile->id, $stats['teacher']->id);
        $this->assertEquals(1, $stats['total_students']);
        $this->assertCount(1, $stats['students']);
        $this->assertEquals(1, $stats['hafalan_today']);
        $this->assertEquals(1, $stats['murajaah_today']);
        $this->assertEquals(1, $stats['hafalan_need_attention']);
        $this->assertEquals(0, $stats['murajaah_need_attention']);
    }

    #[Test]
    public function it_finds_teacher_by_matching_name(): void
    {
        $newUser = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $anotherUser = User::factory()->create(['name' => 'John Doe']);
        $unlinkedTeacher = TeacherProfile::create(['user_id' => $anotherUser->id, 'phone' => '1234']);

        $stats = $this->service->teacherStats($newUser);

        $this->assertEquals($unlinkedTeacher->id, $stats['teacher']->id);
    }

    #[Test]
    public function it_creates_new_teacher_profile_when_no_match(): void
    {
        $newUser = User::factory()->create(['name' => 'New Guy', 'username' => 'newguy']);

        $stats = $this->service->teacherStats($newUser);

        $this->assertNotNull($stats['teacher']);
        $this->assertEquals($newUser->id, $stats['teacher']->user_id);
    }

    #[Test]
    public function it_returns_default_structure_on_exception(): void
    {
        $userMock = \Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('getAttribute')->with('id')->andThrow(new \Exception('Test Exception'));
        $userMock->shouldReceive('getAttribute')->with('teacherProfile')->andReturn(null);

        $stats = $this->service->teacherStats($userMock);

        $this->assertEquals(0, $stats['total_students']);
        $this->assertEquals(0, $stats['hafalan_today']);
        $this->assertEquals(0, $stats['murajaah_today']);
    }
}
