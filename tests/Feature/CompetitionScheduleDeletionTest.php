<?php

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionResult;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\PublicScheduleService;

function deletableSchedule(string $kind = 'sports'): CompetitionSchedule
{
    $edition = IntramuralEdition::create([
        'name' => 'Schedule deletion edition', 'school_year' => '2026-2027',
        'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active',
    ]);
    $sport = Sport::create([
        'name' => $kind === 'cultural' ? 'Painting' : 'Basketball',
        'code' => $kind === 'cultural' ? 'CULT-DELETE-TEST' : 'BASKET-DELETE-TEST', 'status' => 'active',
    ]);
    $configuration = EditionSport::create([
        'edition_id' => $edition->id, 'sport_id' => $sport->id,
        'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active',
    ]);

    return CompetitionSchedule::create([
        'edition_sport_id' => $configuration->id, 'title' => 'Delete test '.$kind,
        'is_activity' => $kind === 'activity', 'starts_at' => '2026-10-20 08:00:00',
        'ends_at' => '2026-10-20 10:00:00', 'venue' => 'MPCC', 'status' => 'scheduled',
    ]);
}

test('administrators can confirm deleting sports cultural and activity schedules', function (string $kind) {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $schedule = deletableSchedule($kind);
    $configuration = $schedule->editionSport;
    $team = Team::create(['edition_id' => $configuration->edition_id, 'name' => 'Test Faction', 'code' => 'TEST-FACTION', 'status' => 'active']);
    $student = Student::factory()->create(['status' => 'active']);
    $entry = AthleteEntry::create([
        'edition_sport_id' => $configuration->id, 'team_id' => $team->id,
        'student_id' => $student->id, 'status' => 'active', 'assigned_at' => now(),
    ]);
    $participant = $schedule->participants()->create(['team_id' => $team->id, 'slot' => 'A', 'status' => 'active']);
    $other = CompetitionSchedule::create([
        'edition_sport_id' => $configuration->id, 'title' => 'Keep this schedule',
        'starts_at' => '2026-10-21 08:00:00', 'ends_at' => '2026-10-21 10:00:00', 'status' => 'scheduled',
    ]);
    $url = route('admin.schedules.matches.index');
    $this->get($url)->assertOk()->assertSee('data-delete-schedule', false)->assertSee('Delete this schedule?');
    expect(app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules'])->toHaveCount(1);

    $this->from($url)->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE'])
        ->assertRedirect($url)->assertSessionHas('success', 'Schedule deleted.');

    $this->assertDatabaseMissing('competition_schedules', ['id' => $schedule->id]);
    $this->assertDatabaseMissing('schedule_participants', ['id' => $participant->id]);
    $this->assertDatabaseHas('competition_schedules', ['id' => $other->id]);
    $this->assertDatabaseHas('athlete_entries', ['id' => $entry->id]);
    $this->assertDatabaseHas('students', ['id' => $student->id]);
    $this->assertDatabaseHas('teams', ['id' => $team->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'competition_schedule.deleted', 'auditable_id' => $schedule->id]);
    expect(app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules'])->toBeEmpty();
})->with(['sports', 'cultural', 'activity']);

test('schedule deletion requires explicit confirmation', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $schedule = deletableSchedule();
    $this->from(route('admin.schedules.matches.index'))->delete(route('admin.schedules.matches.destroy', $schedule))
        ->assertRedirect(route('admin.schedules.matches.index'))->assertSessionHasErrors('confirmation');
    $this->assertDatabaseHas('competition_schedules', ['id' => $schedule->id]);
});

test('schedule deletion keeps bracket wins and sport points and can return to the bracket', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $this->actingAs($admin);
    $schedule = deletableSchedule();
    $configuration = $schedule->editionSport;
    $team = Team::create(['edition_id' => $configuration->edition_id, 'name' => 'Winning Faction', 'code' => 'WINNER', 'status' => 'active']);
    $match = BracketMatch::create([
        'edition_sport_id' => $configuration->id, 'bracket' => 'round_robin',
        'round_number' => 1, 'match_number' => 1, 'status' => 'completed', 'winner_team_id' => $team->id,
    ]);
    $points = SportResult::create([
        'edition_sport_id' => $configuration->id, 'submitted_by' => $admin->id,
        'placements_json' => [1 => $team->id], 'points_json' => [$team->id => 10], 'submitted_at' => now(),
    ]);
    $schedule->update(['bracket_match_id' => $match->id]);
    $this->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE', 'return_to' => 'bracket'])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $configuration->sport_id, 'edition_id' => $configuration->edition_id]));
    expect($match->fresh()->status)->toBe('completed')->and($match->fresh()->winner_team_id)->toBe($team->id)
        ->and($points->fresh()->points_json)->toBe([$team->id => 10]);
});

test('a schedule with saved result records is retained with a clear explanation', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $schedule = deletableSchedule();
    $result = CompetitionResult::create(['competition_schedule_id' => $schedule->id, 'result_data' => ['score' => 10], 'status' => 'approved']);
    $url = route('admin.schedules.matches.index');
    $this->from($url)->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE'])
        ->assertRedirect($url)->assertSessionHasErrors('schedule');
    $this->get($url)->assertOk()->assertSee('This schedule has recorded results.');
    $this->assertDatabaseHas('competition_schedules', ['id' => $schedule->id]);
    $this->assertDatabaseHas('competition_results', ['id' => $result->id]);
    $this->assertDatabaseMissing('audit_logs', ['action' => 'competition_schedule.deleted']);
});

test('other operations roles cannot delete a competition schedule', function (string $role) {
    $schedule = deletableSchedule();
    $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']))
        ->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE'])->assertForbidden();
    $this->assertDatabaseHas('competition_schedules', ['id' => $schedule->id]);
})->with(['gam', 'tabulator', 'coordinator']);
