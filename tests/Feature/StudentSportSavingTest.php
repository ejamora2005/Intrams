<?php

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AthleteEntryService;
use App\Services\EligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function playerSaveScenario(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Save scenario', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $student = Student::factory()->create(['student_number' => 'SEA-UNCHANGED-PLAYER', 'gender' => 'Male', 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    return [$admin, $edition, $team, $student];
}

function playerSaveEvent(IntramuralEdition $edition, string $code, string $type = 'team'): EditionSport
{
    $sport = Sport::create(['name' => $code, 'code' => $code, 'status' => 'active']);

    return $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => $type, 'game_mechanic' => 'round_robin', 'status' => 'active']);
}

function playerSaveEntry(Student $student, EditionSport $event, Team $team, array $attributes = []): AthleteEntry
{
    return AthleteEntry::create([...['student_id' => $student->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'status' => 'active'], ...$attributes]);
}

function studentSportSavePayload(Student $student, array $sportIds): array
{
    // The actual edit form displays the identifier as text and does not submit it.
    return [...$student->only(['first_name', 'middle_name', 'last_name', 'school_year', 'course_id', 'gender', 'year_level', 'section', 'status']), 'edition_sport_ids' => $sportIds, 'return_to_module_index' => 1];
}

test('editing multiple players with the real form payload preserves identifiers and saves a single sport', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Player save edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $sport = Sport::create(['name' => 'Chess (Men)', 'code' => 'CHESS-MEN', 'participant_category' => 'male', 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'individual', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $students = Student::factory()->count(2)->sequence(['student_number' => 'SEA-PLAYER-ONE'], ['student_number' => 'TERRA-PLAYER-TWO'])->create(['gender' => 'Male', 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    foreach ($students as $student) {
        $this->actingAs($admin)->from(route('admin.students.edit', $student))
            ->put(route('admin.students.update', $student), studentSportSavePayload($student, [$event->id]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('athlete_entries', ['student_id' => $student->id, 'edition_sport_id' => $event->id, 'status' => 'active']);
    }
    expect($students[0]->fresh()->student_number)->toBe('SEA-PLAYER-ONE')
        ->and($students[1]->fresh()->student_number)->toBe('TERRA-PLAYER-TWO');
});

test('an explicitly blank identifier is rejected without changing a player or sports', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $event = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        ...studentSportSavePayload($student, [$event->id]), 'student_number' => '',
    ])->assertSessionHasErrors('student_number');
    expect($student->fresh()->student_number)->toBe('SEA-UNCHANGED-PLAYER');
    $this->assertDatabaseCount('athlete_entries', 0);
});

test('correcting an extra major sport keeps selected doubles and separately managed cultural registrations', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $volleyball = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $basketball = playerSaveEvent($edition, 'BASKET-5X5-MEN');
    $cultural = playerSaveEvent($edition, 'CULT-DIGITAL-POSTER');
    $dual = playerSaveEvent($edition, 'BADMINTON-DOUBLES-MEN', 'dual');
    $keep = playerSaveEntry($student, $volleyball, $team, ['medical_certificate_status' => 'verified']);
    $remove = playerSaveEntry($student, $basketball, $team);
    $culturalEntry = playerSaveEntry($student, $cultural, $team);
    $pairKey = (string) Str::uuid();
    $dualEntry = playerSaveEntry($student, $dual, $team, ['pair_key' => $pairKey]);
    $partner = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
    $partnerEntry = playerSaveEntry($partner, $dual, $team, ['pair_key' => $pairKey]);
    expect(app(EligibilityService::class)->evaluateStudent($student, $edition)['possible_dq'])->toBeTrue();

    $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student, [$volleyball->id, $dual->id]))
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.students.index'));
    expect($student->fresh()->student_number)->toBe('SEA-UNCHANGED-PLAYER')
        ->and($keep->fresh()->status)->toBe('active')->and($keep->fresh()->medical_certificate_status)->toBe('verified')
        ->and($remove->fresh()->status)->toBe('inactive')
        ->and($culturalEntry->fresh()->status)->toBe('active')
        ->and($dualEntry->fresh()->status)->toBe('active')->and($dualEntry->fresh()->pair_key)->toBe($pairKey)
        ->and($partnerEntry->fresh()->status)->toBe('active')
        ->and(app(EligibilityService::class)->evaluateStudent($student->fresh(), $edition)['possible_dq'])->toBeFalse();
});

test('reselecting a removed sport reuses its existing row and keeps the player identifier', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $volleyball = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $basketball = playerSaveEvent($edition, 'BASKET-5X5-MEN');
    $old = playerSaveEntry($student, $volleyball, $team);
    foreach ([[$basketball->id], [$volleyball->id], [$basketball->id], [$volleyball->id]] as $selection) {
        $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student, $selection))->assertSessionHasNoErrors();
    }
    expect($old->fresh()->status)->toBe('active')->and($student->fresh()->student_number)->toBe('SEA-UNCHANGED-PLAYER');
    $this->assertDatabaseCount('athlete_entries', 2);
});

test('additional major sports save profile changes and flag the player until corrected', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $volleyball = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $basketball = playerSaveEvent($edition, 'BASKET-5X5-MEN');
    $entry = playerSaveEntry($student, $volleyball, $team);
    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        ...studentSportSavePayload($student, [$volleyball->id, $basketball->id]), 'first_name' => 'Updated Player',
    ])->assertSessionHasNoErrors()->assertSessionHas('participation_warning')->assertRedirect();
    expect($student->fresh()->first_name)->toBe('Updated Player')->and($entry->fresh()->status)->toBe('active');
    $this->assertDatabaseCount('athlete_entries', 2);
    $this->actingAs($admin)->get(route('admin.possible-dq.index'))
        ->assertOk()->assertSee($student->fresh()->full_name)->assertSee('More than 1 major event.');

    $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student->fresh(), [$volleyball->id]))
        ->assertSessionHasNoErrors()->assertSessionMissing('participation_warning');
    expect(app(EligibilityService::class)->flaggedStudents($edition)->pluck('student.id')->all())->not->toContain($student->id);
    $this->actingAs($admin)->get(route('admin.possible-dq.index'))->assertOk()->assertDontSee('Updated Player');
});

test('admin and GAM can create and update players with more than five sports and flag the saved selection', function (string $role) {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $user = $role === 'admin' ? $admin : User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $codes = ['VOLLEYBALL-MEN', 'BASKET-5X5-MEN', 'CHESS-MEN', 'BADMINTON-DOUBLES-MEN', '100M-RUN-MEN', '200M-RUN-MEN'];
    $events = collect($codes)->map(fn (string $code): EditionSport => playerSaveEvent($edition, $code, str_contains($code, 'DOUBLES') ? 'dual' : (str_contains($code, 'RUN') ? 'individual' : 'team')));
    $ids = $events->pluck('id')->all();
    $url = $role === 'admin' ? route('admin.students.update', $student) : route('gam.teams.players.sports.update', [$team, $student]);
    $payload = $role === 'admin' ? studentSportSavePayload($student, $ids) : ['edition_sport_ids' => $ids];
    $this->actingAs($user)->put($url, $payload)->assertSessionHasNoErrors()->assertSessionHas('participation_warning')->assertRedirect();
    expect($student->athleteEntries()->where('status', 'active')->count())->toBe(6);
    $this->get($role === 'admin' ? route('admin.students.index') : route('gam.dashboard', ['team_id' => $team->id]))
        ->assertOk()->assertSee('Open Possible DQ Watchlist');
    $this->get(route($role.'.possible-dq.index'))->assertOk()->assertSee($student->full_name)->assertSee('More than 2 events registered.');

    $corrected = [$events->first()->id];
    $this->put($url, $role === 'admin' ? studentSportSavePayload($student, $corrected) : ['edition_sport_ids' => $corrected])
        ->assertSessionHasNoErrors()->assertSessionMissing('participation_warning');
    $this->get(route($role.'.possible-dq.index'))->assertOk()->assertDontSee($student->student_number);

    // Use a course so automatic admin faction assignment is unambiguous.
    $course = \App\Models\Course::create(['name' => 'Multiple sport course', 'code' => 'MULTI', 'status' => 'active']);
    $team->update(['course_id' => $course->id]);
    $payload = ['first_name' => 'Multiple Sports', 'last_name' => 'New Player', 'gender' => 'Male', 'course_id' => $course->id, 'school_year' => $edition->school_year, 'year_level' => '1st', 'section' => 'A', 'status' => 'active', 'edition_sport_ids' => $ids];
    $url = $role === 'admin' ? route('admin.students.store') : route('gam.teams.players.store', $team);
    $this->post($url, $payload)->assertSessionHasNoErrors()->assertSessionHas('participation_warning')->assertRedirect();
    $newPlayer = Student::where('first_name', 'Multiple Sports')->sole();
    expect($newPlayer->athleteEntries()->where('status', 'active')->count())->toBe(6)
        ->and(app(EligibilityService::class)->evaluateStudent($newPlayer, $edition)['possible_dq'])->toBeTrue();
})->with(['admin', 'gam']);

test('GAM saves selected sports while preserving cultural registrations and selected doubles', function () {
    [, $edition, $team, $student] = playerSaveScenario();
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $sport = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $culturalEntry = playerSaveEntry($student, playerSaveEvent($edition, 'CULT-DIGITAL-POSTER'), $team);
    $pairEntry = playerSaveEntry($student, playerSaveEvent($edition, 'BADMINTON-DOUBLES-MEN', 'dual'), $team, ['pair_key' => (string) Str::uuid()]);
    $this->actingAs($gam)->put(route('gam.teams.players.sports.update', [$team, $student]), ['edition_sport_ids' => [$sport->id, $pairEntry->edition_sport_id]])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($student->fresh()->student_number)->toBe('SEA-UNCHANGED-PLAYER')
        ->and($culturalEntry->fresh()->status)->toBe('active')->and($pairEntry->fresh()->status)->toBe('active');
    $this->assertDatabaseHas('athlete_entries', ['student_id' => $student->id, 'edition_sport_id' => $sport->id, 'status' => 'active']);
});

test('a stale inactive sport returns validation instead of a server error', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $event = playerSaveEvent($edition, 'VOLLEYBALL-MEN');
    $event->sport->update(['status' => 'inactive']);
    $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student, [$event->id]))
        ->assertSessionHasErrors('edition_sport_ids');
    expect($student->fresh()->student_number)->toBe('SEA-UNCHANGED-PLAYER');
    $this->assertDatabaseCount('athlete_entries', 0);
});

test('identifier repair restores only a blank id from verified audit history and is idempotent', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $event = playerSaveEvent($edition, 'CHESS-MEN', 'individual');
    $entry = playerSaveEntry($student, $event, $team);
    $unrelated = Student::factory()->create(['student_number' => 'KEEP-THIS-ID']);
    AuditLog::create([
        'user_id' => $admin->id, 'actor_role' => 'admin', 'action' => 'student.updated',
        'auditable_type' => Student::class, 'auditable_id' => $student->id,
        'old_values' => ['student_number' => 'SEA-UNCHANGED-PLAYER'], 'new_values' => ['student_number' => ''],
        'request_id' => (string) Str::uuid(), 'outcome' => 'success', 'created_at' => now(),
    ]);
    DB::table('students')->where('id', $student->id)->update(['student_number' => '']);
    $before = $student->fresh()->getAttributes();
    $unrelatedBefore = $unrelated->fresh()->getAttributes();
    $migration = require database_path('migrations/2026_10_20_020000_restore_student_identifiers_from_audit_history.php');
    $migration->up();
    $migration->up();
    expect($student->fresh()->getAttributes())->toBe([...$before, 'student_number' => 'SEA-UNCHANGED-PLAYER'])
        ->and($unrelated->fresh()->getAttributes())->toBe($unrelatedBefore)
        ->and($entry->fresh()->status)->toBe('active')
        ->and(AuditLog::where('action', 'student.identifier_restored')->count())->toBe(1);
});

test('identifier repair does not guess an id without history or steal a current identifier', function () {
    [$admin, , , $student] = playerSaveScenario();
    Student::factory()->create(['student_number' => 'ALREADY-TAKEN']);
    DB::table('students')->where('id', $student->id)->update(['student_number' => '']);
    $migration = require database_path('migrations/2026_10_20_020000_restore_student_identifiers_from_audit_history.php');
    $migration->up();
    expect($student->fresh()->student_number)->toBe('');
    AuditLog::create([
        'user_id' => $admin->id, 'actor_role' => 'admin', 'action' => 'student.updated',
        'auditable_type' => Student::class, 'auditable_id' => $student->id,
        'old_values' => ['student_number' => 'ALREADY-TAKEN'], 'new_values' => ['student_number' => ''],
        'request_id' => (string) Str::uuid(), 'outcome' => 'success', 'created_at' => now(),
    ]);
    $migration->up();
    expect($student->fresh()->student_number)->toBe('');
});

test('player selectors hide superseded open sports within the edition and reject stale open submissions', function () {
    [$admin, $edition, $team, $student] = playerSaveScenario();
    $open = playerSaveEvent($edition, 'CHESS', 'individual');
    $men = playerSaveEvent($edition, 'CHESS-MEN', 'individual');
    $women = playerSaveEvent($edition, 'CHESS-WOMEN', 'individual');
    $service = app(AthleteEntryService::class);
    expect($service->selectableSportsFor($edition)->pluck('id')->all())->toContain($men->id, $women->id)->not->toContain($open->id);
    $otherEdition = IntramuralEdition::create(['name' => 'Other edition', 'school_year' => '2026-2027', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-05', 'status' => 'draft']);
    $otherOpen = $otherEdition->editionSports()->create(['sport_id' => $open->sport_id, 'participant_type' => 'individual', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    expect($service->selectableSportsFor($otherEdition)->pluck('id')->all())->toBe([$otherOpen->id]);

    $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student, [$open->id]))
        ->assertSessionHasErrors('edition_sport_ids');
    $this->assertDatabaseCount('athlete_entries', 0);
    $this->actingAs($admin)->put(route('admin.students.update', $student), studentSportSavePayload($student, [$men->id]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('athlete_entries', ['student_id' => $student->id, 'edition_sport_id' => $men->id, 'status' => 'active']);
});
