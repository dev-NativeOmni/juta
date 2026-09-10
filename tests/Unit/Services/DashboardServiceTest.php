<?php

namespace Tests\Unit\Services;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    // ==========================================
    // ADMIN STATS
    // ==========================================

    #[Test]
    public function it_returns_admin_stats_correctly(): void
    {
        $stats = $this->service->adminStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_students', $stats);
        $this->assertEquals(Student::query()->count(), $stats['total_students']);

        $this->assertArrayHasKey('active_students', $stats);
        $this->assertEquals(Student::query()->where('status', 'active')->count(), $stats['active_students']);

        $this->assertArrayHasKey('total_teachers', $stats);
        $this->assertEquals(TeacherProfile::query()->count(), $stats['total_teachers']);

        $this->assertArrayHasKey('total_parents', $stats);
        $this->assertEquals(ParentProfile::query()->count(), $stats['total_parents']);

        $this->assertArrayHasKey('total_programs', $stats);
        $this->assertEquals(Program::query()->count(), $stats['total_programs']);

        $this->assertArrayHasKey('total_class_rooms', $stats);
        $this->assertEquals(ClassRoom::query()->count(), $stats['total_class_rooms']);

        $this->assertArrayHasKey('hafalan_today', $stats);
        $this->assertArrayHasKey('murajaah_today', $stats);
        $this->assertArrayHasKey('active_targets', $stats);
        $this->assertArrayHasKey('students_progress', $stats);
    }

    #[Test]
    public function it_caches_admin_stats(): void
    {
        Cache::forget('admin_dashboard_stats');
        $this->assertFalse(Cache::has('admin_dashboard_stats'));

        $this->service->adminStats();

        $this->assertTrue(Cache::has('admin_dashboard_stats'));
    }

    #[Test]
    public function it_returns_empty_array_on_admin_stats_exception(): void
    {
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Simulated error'));

        $stats = $this->service->adminStats();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    // ==========================================
    // TEACHER STATS
    // ==========================================

    #[Test]
    public function it_returns_correct_stats_for_existing_teacher_with_records(): void
    {
        $teacherUser = $this->teacherUser;
        $student = $this->student;

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
    public function it_returns_default_structure_on_teacher_exception(): void
    {
        $userMock = \Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('getAttribute')->with('id')->andThrow(new \Exception('Test Exception'));
        $userMock->shouldReceive('getAttribute')->with('teacherProfile')->andReturn(null);

        $stats = $this->service->teacherStats($userMock);

        $this->assertEquals(0, $stats['total_students']);
        $this->assertEquals(0, $stats['hafalan_today']);
        $this->assertEquals(0, $stats['murajaah_today']);
    }

    // ==========================================
    // STUDENT STATS
    // ==========================================

    #[Test]
    public function it_creates_student_profile_if_missing_when_fetching_stats(): void
    {
        $user = User::factory()->create([
            'name' => 'New Student User',
            'status' => 'active',
        ]);

        $this->assertNull($user->studentProfile);
        $this->assertDatabaseMissing('students', [
            'user_id' => $user->id,
        ]);

        $stats = $this->service->studentStats($user);

        $this->assertNotNull($stats['student']);
        $this->assertInstanceOf(Student::class, $stats['student']);
        $this->assertEquals($user->id, $stats['student']->user_id);
        $this->assertEquals($user->name, $stats['student']->name);
        $this->assertEquals('active', $stats['student']->status);

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'name' => $user->name,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_fetches_stats_for_user_with_existing_student_profile(): void
    {
        $user = clone $this->studentUser;
        $user->load('studentProfile');

        $this->assertNotNull($user->studentProfile);

        $stats = $this->service->studentStats($user);

        $this->assertArrayHasKey('student', $stats);
        $this->assertEquals($this->student->id, $stats['student']->id);
        $this->assertArrayHasKey('progress', $stats);
        $this->assertIsArray($stats['progress']);
        $this->assertArrayHasKey('summary', $stats);
        $this->assertIsArray($stats['summary']);
        $this->assertArrayHasKey('motivation', $stats);
    }

    // ==========================================
    // PARENT STATS
    // ==========================================

    #[Test]
    public function it_creates_parent_profile_if_not_exists_when_fetching_parent_stats(): void
    {
        $roleParent = Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orangtua']);
        $userWithoutProfile = User::factory()->create([
            'role_id' => $roleParent->id,
            'name' => 'Parent Without Profile',
        ]);

        $this->assertNull($userWithoutProfile->parentProfile);

        $stats = $this->service->parentStats($userWithoutProfile);

        $this->assertNotNull($userWithoutProfile->parentProfile);
        $this->assertEquals($userWithoutProfile->id, $userWithoutProfile->parentProfile->user_id);
        $this->assertArrayHasKey('parent', $stats);
        $this->assertEquals($userWithoutProfile->id, $stats['parent']->user_id);

        $this->assertDatabaseHas('parent_profiles', [
            'user_id' => $userWithoutProfile->id,
        ]);
    }

    #[Test]
    public function it_does_not_create_parent_profile_if_already_exists(): void
    {
        $existingProfileId = $this->parentProfile->id;

        $this->assertNotNull($this->parentUser->parentProfile);
        $initialProfileCount = ParentProfile::count();

        $stats = $this->service->parentStats($this->parentUser);

        $this->assertArrayHasKey('parent', $stats);
        $this->assertEquals($existingProfileId, $stats['parent']->id);
        $this->assertEquals($initialProfileCount, ParentProfile::count());
    }
}
