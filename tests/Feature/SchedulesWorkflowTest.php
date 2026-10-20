<?php

use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\CompetitionResult;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\PublicProgramme;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\User;
use App\Services\PublicScheduleService;

function schedulesWorkflow(): array
{
    $edition = IntramuralEdition::create(['name' => config('intramurals.default_edition.name'), 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $i) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'WF-'.$i, 'status' => 'active']));
    $sport = Sport::create(['name' => 'Volleyball (Men)', 'code' => 'VOLLEYBALL-MEN', 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $competitors = $teams->map(fn ($team) => BracketCompetitor::create(['edition_sport_id' => $event->id, 'type' => 'team', 'team_id' => $team->id, 'identity_key' => 'team:'.$team->id, 'label' => $team->name]));
    $match = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitors[1]->id, 'competitor_two_id' => $competitors[0]->id, 'team_one_id' => $teams[1]->id, 'team_two_id' => $teams[0]->id, 'status' => 'pending']);
    $payload = ['edition_id' => $edition->id, 'event' => 'Workflow event', 'date' => '2026-10-22', 'session' => 'PM', 'starts_at' => '14:00', 'ends_at' => null, 'category' => 'sports', 'venue' => 'MPC', 'is_published' => 1, 'games_present' => 1, 'games' => []];

    return compact('edition', 'admin', 'teams', 'event', 'competitors', 'match', 'payload');
}

test('Schedules owns creation editing and deletion while Live Competition has no CRUD controls', function () {
    extract(schedulesWorkflow());
    $this->actingAs($admin)->get(route('admin.competition.index'))->assertOk()->assertSee('Manage schedules')->assertDontSee('data-delete-schedule', false)->assertDontSee('Save schedule')->assertDontSee('+ Add schedule');
    $this->get(route('admin.schedules.matches.index'))->assertOk()->assertSee('+ Add schedule')->assertSee('Public timetable');
    $this->get(route('admin.schedules.index'))->assertOk()->assertSee('+ Add public schedule')->assertSee('Create schedule')->assertSee('Delete schedule');
    $minimal = $payload;
    unset($minimal['venue'], $minimal['starts_at'], $minimal['ends_at']);
    $this->post(route('admin.schedules.store'), $minimal)->assertSessionHasNoErrors();
    expect(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->firstWhere('event', 'Workflow event'))->toMatchArray(['time' => 'PM', 'venue' => 'TBA']);
    $created = collect(PublicProgramme::sole()->events)->firstWhere('event', 'Workflow event');
    $this->assertDatabaseHas('audit_logs', ['action' => 'public_schedule.created']);
    $this->put(route('admin.schedules.update', $created['key']), [...$payload, 'entry_key' => $created['key'], 'version' => 1, 'event' => 'Edited workflow'])->assertSessionHasNoErrors();
    expect(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->pluck('event'))->toContain('Edited workflow');
    $this->delete(route('admin.schedules.destroy', $created['key']), ['edition_id' => $edition->id, 'version' => 2])->assertSessionHasErrors('confirmation');
    $this->delete(route('admin.schedules.destroy', $created['key']), ['edition_id' => $edition->id, 'version' => 1, 'confirmation' => 'DELETE'])->assertSessionHasErrors('version');
    $this->delete(route('admin.schedules.destroy', $created['key']), ['edition_id' => $edition->id, 'version' => 2, 'confirmation' => 'DELETE'])->assertRedirect(route('admin.schedules.index'));
    expect(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->pluck('event'))->not->toContain('Edited workflow');
    $this->assertDatabaseHas('audit_logs', ['action' => 'public_schedule.deleted']);
    $this->post('/admin/competition/schedules', $payload)->assertNotFound();
});

test('linked public games follow actual bracket teams winner and timing with editable overrides', function () {
    extract(schedulesWorkflow());
    $game = ['home' => 'team:trojan-warriors', 'away' => 'team:sea-dragons', 'bracket_match_ids' => [$match->id], 'manual_teams' => 0];
    $this->actingAs($admin)->post(route('admin.schedules.store'), [...$payload, 'games' => [$game]])->assertSessionHasNoErrors();
    $created = collect(PublicProgramme::sole()->events)->firstWhere('event', 'Workflow event');
    $read = fn () => app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->firstWhere('event', 'Workflow event')['matchups'][0];
    expect($read()['home'])->toMatchArray(['name' => 'Terraquatic Eagles', 'logo_path' => 'images/logo/terraquatic_eagles.png']);
    expect(CompetitionSchedule::count())->toBe(0);
    $game = [...$game, 'update_bracket_schedule' => 1, 'bracket_starts_at' => '2026-10-22T14:25', 'bracket_ends_at' => '2026-10-22T16:05', 'bracket_venue' => 'SSC'];
    $this->put(route('admin.schedules.update', $created['key']), [...$payload, 'games' => [$game], 'entry_key' => $created['key'], 'version' => 1])->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::sole();
    expect($match->fresh()->schedule->id)->toBe($schedule->id)->and($schedule->starts_at->format('H:i'))->toBe('14:25')->and($schedule->venue)->toBe('SSC');
    $match->update(['status' => 'completed', 'winner_competitor_id' => $competitors[1]->id, 'winner_team_id' => $teams[1]->id]);
    $score = SportResult::create(['edition_sport_id' => $event->id, 'placements_json' => [1 => $teams[1]->id], 'points_json' => [$teams[1]->id => 20], 'submitted_at' => now()]);
    $result = CompetitionResult::create(['competition_schedule_id' => $schedule->id, 'result_data' => ['winner_team_id' => $teams[1]->id], 'status' => 'approved', 'submitted_by' => $admin->id, 'submitted_at' => now()]);
    $before = [$match->fresh()->getAttributes(), $score->fresh()->getAttributes(), $result->fresh()->getAttributes()];
    $game['manual_teams'] = 1;
    $game['bracket_starts_at'] = '2026-10-22T15:00';
    $game['bracket_ends_at'] = '2026-10-22T16:30';
    $this->put(route('admin.schedules.update', $created['key']), [...$payload, 'games' => [$game], 'entry_key' => $created['key'], 'version' => 2])->assertSessionHasNoErrors();
    expect($read()['home']['name'])->toBe('Trojan Warriors')->and($read()['winner'])->toBe('Terraquatic Eagles')->and($schedule->fresh()->starts_at->format('H:i'))->toBe('15:00')
        ->and([$match->fresh()->getAttributes(), $score->fresh()->getAttributes(), $result->fresh()->getAttributes()])->toBe($before);
    $this->delete(route('admin.schedules.destroy', $created['key']), ['edition_id' => $edition->id, 'version' => 3, 'confirmation' => 'DELETE'])->assertSessionHasNoErrors();
    expect($schedule->fresh())->not->toBeNull()->and($match->fresh()->winner_team_id)->toBe($teams[1]->id);
    $this->from(route('admin.schedules.matches.index'))->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE'])->assertSessionHasErrors('schedule');
});

test('multiple divisions can link to one programme game and invalid bracket timing or foreign matches cannot save', function () {
    extract(schedulesWorkflow());
    $other = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 2, 'match_number' => 1, 'team_one_id' => $teams[2]->id, 'team_two_id' => $teams[0]->id, 'status' => 'pending']);
    $this->actingAs($admin)->post(route('admin.schedules.store'), [...$payload, 'games' => [['bracket_match_ids' => [$match->id, $other->id]]]])->assertSessionHasNoErrors();
    expect(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->firstWhere('event', 'Workflow event')['matchups'])->toHaveCount(2);
    $this->post(route('admin.schedules.store'), [...$payload, 'games' => [['bracket_match_ids' => [$match->id], 'update_bracket_schedule' => 1]]])->assertSessionHasErrors();
    $this->post(route('admin.schedules.store'), [...$payload, 'games' => [['bracket_match_ids' => [999999]]]])->assertSessionHasErrors();
    expect(CompetitionSchedule::count())->toBe(0)->and(PublicProgramme::count())->toBe(1);
});

test('deleting the last public event keeps the programme empty instead of restoring defaults', function () {
    extract(schedulesWorkflow());
    $this->actingAs($admin)->post(route('admin.schedules.store'), $payload)->assertSessionHasNoErrors();
    $programme = PublicProgramme::sole();
    $created = collect($programme->events)->firstWhere('event', 'Workflow event');
    $programme->update(['events' => [$created]]);
    $this->delete(route('admin.schedules.destroy', $created['key']), ['edition_id' => $edition->id, 'version' => 1, 'confirmation' => 'DELETE'])->assertSessionHasNoErrors();
    expect(PublicProgramme::sole()->events)->toBe([])->and(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules'])->toBeEmpty();
});

test('a saved public link follows the same bracket position after a lineup rebuild replaces match IDs', function () {
    extract(schedulesWorkflow());
    $this->actingAs($admin)->post(route('admin.schedules.store'), [...$payload, 'games' => [['bracket_match_ids' => [$match->id]]]])->assertSessionHasNoErrors();
    $match->delete();
    $replacement = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitors[2]->id, 'competitor_two_id' => $competitors[0]->id, 'status' => 'pending']);
    $row = app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->firstWhere('event', 'Workflow event')['matchups'][0];
    expect($row['home']['name'])->toBe('Trojan Warriors')->and($row['schedule_url'])->toContain('bracket_match_id='.$replacement->id);
    $this->get(route('admin.schedules.index', ['search' => 'Workflow event']))->assertOk()->assertSee('Trojan Warriors');
});

test('public schedule creation and deletion require an active administrator', function (string $role) {
    extract(schedulesWorkflow());
    $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']))->post(route('admin.schedules.store'), $payload)->assertForbidden();
    $this->delete(route('admin.schedules.destroy', str_repeat('a', 24)), ['edition_id' => $edition->id, 'version' => 1, 'confirmation' => 'DELETE'])->assertForbidden();
})->with(['gam', 'tabulator', 'coordinator']);

test('match schedule CRUD links one bracket match without duplicate schedules or changes to bracket slots', function () {
    extract(schedulesWorkflow());
    $this->actingAs($admin);
    $data = ['edition_id' => $edition->id, 'edition_sport_id' => $event->id, 'bracket_match_id' => $match->id, 'title' => 'Linked finals', 'starts_at' => '2026-10-22T14:35', 'ends_at' => '2026-10-22T16:10', 'venue' => 'MPC', 'status' => 'scheduled'];
    $before = $match->fresh()->getAttributes();
    $this->post(route('admin.schedules.matches.store'), $data)->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::sole();
    expect($schedule->bracket_match_id)->toBe($match->id);
    $this->get(route('admin.schedules.matches.create', ['bracket_match_id' => $match->id]))->assertRedirect(route('admin.schedules.matches.edit', $schedule));
    $this->get(route('admin.schedules.matches.edit', $schedule))->assertOk()->assertSee('Edit match schedule')->assertSee('2026-10-22T14:35');
    $this->post(route('admin.schedules.matches.store'), $data)->assertSessionHasErrors('bracket_match_id');
    $this->put(route('admin.schedules.matches.update', $schedule), [...$data, 'title' => 'Edited linked finals', 'starts_at' => '2026-10-22T15:10'])->assertSessionHasNoErrors();
    expect(CompetitionSchedule::count())->toBe(1)->and($schedule->fresh()->starts_at->format('H:i'))->toBe('15:10')->and($match->fresh()->getAttributes())->toEqual($before);
    $this->get(route('admin.competition.index'))->assertOk()->assertSee('Open bracket')->assertDontSee('data-delete-schedule', false)->assertDontSee('Edit schedule');
    $this->delete(route('admin.schedules.matches.destroy', $schedule), ['confirmation' => 'DELETE'])->assertRedirect(route('admin.schedules.matches.index'));
    expect($match->fresh())->not->toBeNull()->and(CompetitionSchedule::count())->toBe(0);
});

test('a foreign edition bracket cannot be linked and saved results cannot be moved to another match', function () {
    extract(schedulesWorkflow());
    $foreign = IntramuralEdition::create(['name' => 'Other year', 'school_year' => '2027-2028', 'starts_on' => '2027-10-19', 'ends_on' => '2027-10-23', 'status' => 'draft']);
    $foreignEvent = EditionSport::create(['edition_id' => $foreign->id, 'sport_id' => $event->sport_id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $foreignMatch = BracketMatch::create(['edition_sport_id' => $foreignEvent->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'pending']);
    $this->actingAs($admin)->post(route('admin.schedules.store'), [...$payload, 'games' => [['bracket_match_ids' => [$foreignMatch->id]]]])->assertSessionHasErrors();
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'bracket_match_id' => $match->id, 'starts_at' => '2026-10-22 08:00:00', 'ends_at' => '2026-10-22 10:00:00', 'venue' => 'MPC', 'status' => 'completed']);
    $match->update(['status' => 'completed', 'winner_team_id' => $teams[1]->id]);
    $update = ['title' => 'Completed event', 'bracket_match_id' => null, 'starts_at' => '2026-10-22T09:00', 'ends_at' => '2026-10-22T11:00', 'venue' => 'SSC', 'status' => 'completed'];
    $this->put(route('admin.schedules.matches.update', $schedule), $update)->assertSessionHasErrors('bracket_match_id');
    $this->put(route('admin.schedules.matches.update', $schedule), [...$update, 'bracket_match_id' => $match->id])->assertSessionHasNoErrors();
    expect($schedule->fresh()->bracket_match_id)->toBe($match->id)->and($match->fresh()->winner_team_id)->toBe($teams[1]->id)->and($schedule->fresh()->venue)->toBe('SSC');
});

test('activity schedule editing shows its activity name and relevant fields', function () {
    extract(schedulesWorkflow());
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'title' => 'Campus fun run', 'is_activity' => true, 'starts_at' => '2026-10-22 06:00:00', 'ends_at' => '2026-10-22 08:00:00', 'venue' => 'Field/Oval', 'status' => 'scheduled']);
    $this->actingAs($admin)->get(route('admin.schedules.matches.edit', $schedule))->assertOk()->assertSee('Edit activity schedule')->assertSee('Campus fun run')->assertSee('datetime-local', false)->assertDontSee('name="bracket_match_id"', false);
    $schedule->update(['title' => 'Ruperto Run 2026', 'is_activity' => false]);
    $this->get(route('admin.schedules.matches.edit', $schedule))->assertOk()->assertSee('Edit activity schedule')->assertSee('Ruperto Run 2026')->assertDontSee('name="bracket_match_id"', false);
});
