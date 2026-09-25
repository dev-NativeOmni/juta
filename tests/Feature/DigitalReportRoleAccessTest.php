<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Support\SidebarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Koordinator Keagamaan (supervisor) setara Koordinator Tahfizh & Tanse untuk rapor;
 * Pendamping Adab hanya melihat rapor kelas dampingannya, tanpa cetak.
 */
class DigitalReportRoleAccessTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Akses', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Akses', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $this->classRoom->id]);
    }

    private function userWithRole(string $role, string $label): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['display_name' => $label])->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function koordinator_keagamaan_can_view_and_print_every_rapor(): void
    {
        $supervisor = $this->userWithRole('supervisor', 'Koordinator Keagamaan');

        $this->actingAs($supervisor)->get(route('digital-reports.index'))->assertOk()->assertSee($this->student->name);
        $this->actingAs($supervisor)->get(route('digital-reports.show', $this->student))->assertOk()->assertSee('Cetak / Simpan PDF');
        $this->actingAs($supervisor)->get(route('digital-reports.print', $this->student))->assertOk();
        $this->actingAs($supervisor)->get(route('digital-reports.class-print', $this->classRoom))->assertOk();

        $this->actingAs($supervisor);
        $urls = collect(SidebarMenu::for($supervisor))->flatMap(fn ($g) => array_column($g['items'], 'url'));
        $this->assertContains(route('digital-reports.index'), $urls->all());
        $this->assertContains(route('academic-calendar.index'), $urls->all(), 'Koordinator Keagamaan mendapat Kalender Adab.');
    }

    #[Test]
    public function pendamping_adab_can_view_but_not_print(): void
    {
        $pendamping = $this->userWithRole('pendamping_adab', 'Pendamping Adab');
        $this->classRoom->update(['pendamping_adab_id' => $pendamping->id]);

        $this->actingAs($pendamping)->get(route('digital-reports.show', $this->student))
            ->assertOk()
            ->assertDontSee('Cetak / Simpan PDF');
        $this->actingAs($pendamping)->get(route('digital-reports.index', ['class_room_id' => $this->classRoom->id]))
            ->assertOk()
            ->assertDontSee('Cetak Rapor Satu Kelas');
        $this->actingAs($pendamping)->get(route('digital-reports.print', $this->student))->assertForbidden();
        $this->actingAs($pendamping)->get(route('digital-reports.class-print', $this->classRoom))->assertForbidden();

        $this->actingAs($pendamping);
        $urls = collect(SidebarMenu::for($pendamping))->flatMap(fn ($g) => array_column($g['items'], 'url'));
        $this->assertNotContains(route('academic-calendar.index'), $urls->all(), 'Kalender Adab bukan untuk Pendamping Adab.');
    }
}
