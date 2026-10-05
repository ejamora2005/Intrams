<?php

use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

function activeAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function validStudentData(array $overrides = []): array
{
    return array_merge([
        'student_number' => '2026-00001',
        'first_name' => 'Ana',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'school_year' => '2026-2027',
        'gender' => 'Female',
        'year_level' => '1st',
        'section' => 'A',
        'status' => 'active',
    ], $overrides);
}

test('admin can browse and search student athlete records', function () {
    $admin = activeAdmin();
    Student::factory()->create(['student_number' => '2026-10001', 'first_name' => 'Lina', 'last_name' => 'Garcia']);
    Student::factory()->create(['student_number' => '2026-10002', 'first_name' => 'Marco', 'last_name' => 'Dela Cruz']);

    $response = $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'Lina']));

    $response->assertOk()->assertSee('Lina')->assertDontSee('Marco');
});

test('admin student records show joined sports and events automatically', function () {
    $admin = activeAdmin();
    $edition = IntramuralEdition::query()->create([
        'name' => 'Active Intramurals',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-19',
        'ends_on' => '2026-10-23',
        'status' => 'active',
    ]);
    $archivedEdition = IntramuralEdition::query()->create([
        'name' => 'Archived Intramurals',
        'school_year' => '2025-2026',
        'starts_on' => '2025-10-19',
        'ends_on' => '2025-10-23',
        'status' => 'archived',
    ]);
    $student = Student::factory()->create([
        'student_number' => '2026-10003',
        'first_name' => 'Karyl',
        'last_name' => 'Gesto',
        'status' => 'active',
    ]);
    $russianSoftball = Sport::query()->create(['name' => 'Russian Softball', 'code' => 'RUSSIAN-SOFTBALL', 'status' => 'active']);
    $painting = Sport::query()->create(['name' => 'Cultural: Collaborative Painting', 'code' => 'CULT-COLLAB-PAINT', 'status' => 'active']);
    $oldChess = Sport::query()->create(['name' => 'Old Chess', 'code' => 'OLD-CHESS', 'status' => 'active']);
    $russianSoftballConfig = $edition->editionSports()->create(['sport_id' => $russianSoftball->id, 'participant_type' => 'team', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']);
    $paintingConfig = $edition->editionSports()->create(['sport_id' => $painting->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'preparation']);
    $oldChessConfig = $archivedEdition->editionSports()->create(['sport_id' => $oldChess->id, 'participant_type' => 'individual', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']);

    foreach ([$russianSoftballConfig, $paintingConfig, $oldChessConfig] as $editionSport) {
        AthleteEntry::query()->create([
            'edition_sport_id' => $editionSport->id,
            'student_id' => $student->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['search' => 'Karyl']))
        ->assertOk()
        ->assertSee('Joined sports/events')
        ->assertSee('Russian Softball')
        ->assertSee('Cultural: Collaborative Painting')
        ->assertDontSee('Old Chess');
});

test('admin can create a student and the action is audited', function () {
    $admin = activeAdmin();

    $response = $this->actingAs($admin)->post(route('admin.students.store'), validStudentData([
        'first_name' => '  aNA  ',
        'middle_name' => '<b>sANTOS</b>',
        'last_name' => 'rEYES',
        'section' => 'a',
    ]));

    $response->assertRedirect(route('admin.students.index'));
    $this->assertDatabaseHas('students', ['student_number' => '2026-00001', 'first_name' => 'Ana', 'middle_name' => 'Santos', 'last_name' => 'Reyes', 'school_year' => '2026-2027', 'section' => 'A', 'status' => 'active']);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'student.created', 'outcome' => 'success']);
});

test('student number must be unique', function () {
    $admin = activeAdmin();
    Student::factory()->create(['student_number' => '2026-00001']);

    $this->actingAs($admin)
        ->from(route('admin.students.create'))
        ->post(route('admin.students.store'), validStudentData())
        ->assertRedirect(route('admin.students.create'))
        ->assertSessionHasErrors('student_number');
});

test('admin can update, archive, and restore a student without a permanent deletion', function () {
    $admin = activeAdmin();
    $student = Student::factory()->create(['student_number' => '2026-00001', 'first_name' => 'Ana', 'last_name' => 'Reyes']);

    $this->actingAs($admin)
        ->put(route('admin.students.update', $student), validStudentData(['first_name' => 'Anabel', 'status' => 'inactive']))
        ->assertRedirect(route('admin.students.index'));
    $this->assertDatabaseHas('students', ['id' => $student->id, 'first_name' => 'Anabel', 'status' => 'inactive']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.updated']);

    $this->actingAs($admin)->delete(route('admin.students.destroy', $student))->assertRedirect(route('admin.students.index'));
    $this->assertSoftDeleted('students', ['id' => $student->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.archived']);

    $this->actingAs($admin)->post(route('admin.students.restore', $student->id))->assertRedirect(route('admin.students.index', ['status' => 'archived']));
    $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.restored']);
});

test('admin sees the active faction and can transfer a student without losing event registration', function () {
    $admin = activeAdmin();
    $edition = IntramuralEdition::query()->create([
        'name' => 'Transfer Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-06',
        'status' => 'active',
    ]);
    $trojan = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Trojan Warriors', 'code' => 'trojan-warriors', 'status' => 'active']);
    $eagles = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Terraquatic Eagles', 'code' => 'terraquatic-eagles', 'status' => 'active']);
    $student = Student::factory()->create(['student_number' => '2310018-1', 'first_name' => 'Danilo', 'last_name' => 'Dumagat', 'status' => 'active']);
    TeamMember::query()->create(['edition_id' => $edition->id, 'team_id' => $trojan->id, 'student_id' => $student->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    $volleyball = Sport::query()->create(['name' => 'Volleyball', 'code' => 'VOLLEYBALL', 'status' => 'active']);
    $editionSport = $edition->editionSports()->create(['sport_id' => $volleyball->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active']);
    $entry = AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $trojan->id, 'status' => 'active', 'assigned_at' => now()]);

    $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'Danilo']))
        ->assertOk()->assertSee('Faction')->assertSee('Trojan Warriors');
    $this->actingAs($admin)->get(route('admin.students.edit', $student))
        ->assertOk()->assertSee('Transfer student')->assertSee('Terraquatic Eagles');

    $this->actingAs($admin)->put(route('admin.students.team.update', $student), ['team_id' => $eagles->id])
        ->assertRedirect(route('admin.students.edit', $student));

    $this->assertDatabaseHas('team_members', ['student_id' => $student->id, 'edition_id' => $edition->id, 'team_id' => $eagles->id]);
    $this->assertDatabaseHas('athlete_entries', ['id' => $entry->id, 'team_id' => $eagles->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'team.member.transferred']);

    $this->actingAs($admin)->put(route('admin.students.update', $student), validStudentData(['student_number' => $student->student_number]))
        ->assertRedirect(route('admin.students.index'));
    $this->assertDatabaseHas('team_members', ['student_id' => $student->id, 'edition_id' => $edition->id, 'team_id' => $eagles->id]);

    SportResult::query()->create(['edition_sport_id' => $editionSport->id, 'submitted_by' => $admin->id, 'placements_json' => [], 'points_json' => [], 'submitted_at' => now()]);
    $this->actingAs($admin)->put(route('admin.students.team.update', $student), ['team_id' => $trojan->id])
        ->assertStatus(422);
    $this->assertDatabaseHas('team_members', ['student_id' => $student->id, 'edition_id' => $edition->id, 'team_id' => $eagles->id]);

    $this->actingAs(User::factory()->create(['role' => 'gam', 'status' => 'active']))
        ->put(route('admin.students.team.update', $student), ['team_id' => $trojan->id])
        ->assertForbidden();
});

test('coordinators cannot manage student records', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get(route('admin.students.index'))->assertForbidden();
    $this->actingAs($coordinator)->post(route('admin.students.store'), validStudentData())->assertForbidden();
});
