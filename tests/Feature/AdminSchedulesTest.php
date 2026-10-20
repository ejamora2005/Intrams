<?php

use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\PublicProgramme;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\PublicProgrammeService;
use App\Services\PublicScheduleService;
use Illuminate\Support\Facades\DB;

function adminScheduleScenario(): array
{
    $edition = IntramuralEdition::create(['name' => config('intramurals.default_edition.name'), 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $event = app(PublicProgrammeService::class)->eventsFor($edition)->first();
    $payload = [
        'edition_id' => $edition->id, 'entry_key' => $event['key'], 'version' => 0,
        'event' => $event['event'], 'date' => $event['date'], 'session' => $event['session'],
        'starts_at' => null, 'ends_at' => null, 'category' => $event['category'], 'venue' => 'GYM', 'is_published' => true,
    ];

    return [$edition, $admin, $event, $payload];
}

test('Schedules lists the public events with search date category edit dialogs and an authenticated public preview', function () {
    [, $admin] = adminScheduleScenario();
    $this->actingAs($admin)->get(route('admin.schedules.index'))->assertOk()
        ->assertSee('Schedules')->assertSee('Showing 28 of 28 sessions')->assertSee('Edit schedule')
        ->assertSee('name="starts_at"', false)->assertSee('data-ops-modal', false)->assertSee('Philippine time (UTC+8)');
    $this->get(route('admin.schedules.index', ['search' => 'jumps', 'date' => '2026-10-21', 'category' => 'sports']))
        ->assertOk()->assertSee('Showing 1 of 28 sessions')->assertSee('Jumps M/W')->assertDontSee('Awarding Mugna');
    $this->get(route('public.schedule', ['date' => '2026-10-21']))->assertOk()->assertSee('Awarding Mugna')->assertSee('Dashboard');
    $this->get(route('public.schedule', ['date' => ['invalid']]))->assertOk();
    $this->assertDatabaseCount('public_programmes', 0);
});

test('admin edits persist to the public timetable while saved sports schedules results and points remain unchanged', function () {
    [$edition, $admin, $event, $payload] = adminScheduleScenario();
    $sport = Sport::create(['code' => 'BASKET-3X3-MEN', 'name' => 'Basketball 3x3 (Men)', 'status' => 'active']);
    $configuration = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $match = BracketMatch::create(['edition_sport_id' => $configuration->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'team_one_id' => $team->id, 'winner_team_id' => $team->id, 'status' => 'completed']);
    CompetitionSchedule::create(['edition_sport_id' => $configuration->id, 'bracket_match_id' => $match->id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 09:00:00', 'status' => 'completed']);
    SportResult::create(['edition_sport_id' => $configuration->id, 'placements_json' => [1 => $team->id], 'points_json' => [$team->id => 15], 'submitted_at' => '2026-10-20 01:15:00']);
    TeamTally::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'points' => 15, 'updated_at' => '2026-10-20 01:15:00']);
    $tables = ['intramural_editions', 'sports', 'edition_sports', 'competition_schedules', 'competition_results', 'bracket_matches', 'bracket_competitors', 'sport_results', 'team_tallies', 'students', 'athlete_entries', 'team_members'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    $before = $snapshot();
    $payload = [...$payload, 'event' => 'Edited Badminton', 'date' => '2026-10-22', 'starts_at' => '09:15', 'ends_at' => '11:45', 'venue' => 'MPCC', 'category' => 'cultural'];
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), $payload)
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.schedules.index', ['date' => '2026-10-22']).'#schedule-'.$event['key']);
    $programme = PublicProgramme::sole();
    expect($programme->events)->toHaveCount(28)->and($programme->updated_by)->toBe($admin->id)->and($snapshot())->toBe($before);
    $public = app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->firstWhere('event', 'Edited Badminton');
    expect($public)->toMatchArray(['time' => '9:15 AM', 'cutoff' => 'Until 11:45 AM', 'venue' => 'MPCC', 'category' => 'cultural']);
    $this->get(route('admin.schedules.index', ['search' => 'Edited Badminton']))->assertOk()->assertSee('Showing 1 of 28 sessions');
    $this->assertDatabaseHas('audit_logs', ['action' => 'public_schedule.updated', 'auditable_id' => $programme->id]);
    config(['public_programme.events' => [['event' => 'Old local default must not return']]]);
    expect(app(PublicProgrammeService::class)->eventsFor($edition))->toHaveCount(28)
        ->and(app(PublicScheduleService::class)->today('2026-10-22')['todaySchedules']->pluck('event')->all())->toContain('Edited Badminton')->not->toContain('Old local default must not return');
    expect($snapshot())->toBe($before);
});

test('hidden schedules stay editable and a fully hidden programme never falls back to the original list', function () {
    [$edition, $admin, $event, $payload] = adminScheduleScenario();
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), [...$payload, 'is_published' => false])->assertSessionHasNoErrors();
    expect(app(PublicScheduleService::class)->today('2026-10-19')['todaySchedules'])->toHaveCount(1);
    $this->get(route('admin.schedules.index', ['date' => '2026-10-19']))->assertOk()->assertSee('Hidden')->assertSee('Showing 2 of 28 sessions');
    $programme = PublicProgramme::sole();
    $programme->update(['events' => collect($programme->events)->map(fn ($row) => [...$row, 'is_published' => false])->all()]);
    expect(app(PublicScheduleService::class)->today('2026-10-19')['todaySchedules'])->toBeEmpty();
    $this->put(route('admin.schedules.update', $event['key']), [...$payload, 'version' => 1, 'is_published' => true])->assertSessionHasNoErrors();
    expect(app(PublicScheduleService::class)->today('2026-10-19')['todaySchedules'])->toHaveCount(1);
});

test('stale edits cannot overwrite another saved schedule or recreate the programme', function () {
    [, $admin, $event, $payload] = adminScheduleScenario();
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), [...$payload, 'event' => 'Latest name'])->assertSessionHasNoErrors();
    $before = PublicProgramme::sole()->getAttributes();
    $this->put(route('admin.schedules.update', $event['key']), [...$payload, 'event' => 'Stale name'])->assertSessionHasErrors('version');
    expect(PublicProgramme::sole()->getAttributes())->toBe($before);
});

test('edited start times arrange timed events chronologically within their session', function () {
    [$edition, $admin] = adminScheduleScenario();
    $service = app(PublicProgrammeService::class);
    $morning = $service->eventsFor($edition)->where('date', '2026-10-20')->where('session', 'AM')->values();
    $service->updateEvent($edition, $morning[0]['key'], ['starts_at' => '10:30'], 0, $admin);
    $service->updateEvent($edition, $morning[1]['key'], ['starts_at' => '08:15'], 0, $admin);
    $names = [$morning[0]['event'], $morning[1]['event']];
    $public = app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules'];
    expect($public->where('period', 'Morning')->whereIn('event', $names)->pluck('time')->all())->toBe(['8:15 AM', '10:30 AM']);
    $this->actingAs($admin)->get(route('admin.schedules.index', ['date' => '2026-10-20']))
        ->assertOk()->assertSeeInOrder([$morning[1]['event'], $morning[0]['event']]);
});

test('the default public schedule date follows Philippine midnight while stored timestamps remain UTC', function () {
    adminScheduleScenario();
    $this->travelTo(\Carbon\Carbon::parse('2026-10-19 16:30:00', 'UTC'));
    expect(config('app.timezone'))->toBe('UTC')
        ->and(app(PublicScheduleService::class)->today()['selectedDate'])->toBe('2026-10-20');
    $this->get(route('public.schedule'))->assertOk()->assertSee('Basketball 3x3 (MW)');
});

test('invalid schedule details are rejected without saving partial changes', function (array $changes, string $field) {
    [, $admin, $event, $payload] = adminScheduleScenario();
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), [...$payload, ...$changes])->assertSessionHasErrors($field);
    $this->get(route('admin.schedules.index'))->assertOk();
    $this->assertDatabaseCount('public_programmes', 0);
})->with([
    [['event' => ''], 'event'], [['date' => '2026-10-24'], 'date'], [['starts_at' => '25:00'], 'starts_at'],
    [['starts_at' => '09:00', 'ends_at' => '08:00'], 'ends_at'], [['starts_at' => '19:00', 'session' => 'AM'], 'session'],
    [['category' => 'other'], 'category'], [['is_published' => 'invalid'], 'is_published'],
    [['event' => ['invalid']], 'event'], [['entry_key' => ['invalid']], 'entry_key'],
]);

test('schedule editing is limited to active administrators', function (string $role, string $status) {
    [, , $event, $payload] = adminScheduleScenario();
    $user = User::factory()->create(['role' => $role, 'status' => $status]);
    $this->actingAs($user)->get(route('admin.schedules.index'))->assertForbidden();
    $this->put(route('admin.schedules.update', $event['key']), $payload)->assertForbidden();
    $this->assertDatabaseCount('public_programmes', 0);
})->with([['gam', 'active'], ['tabulator', 'active'], ['coordinator', 'active'], ['admin', 'inactive']]);

test('missing active editions unknown event keys and another edition cannot create a public programme', function () {
    [$edition, $admin, $event, $payload] = adminScheduleScenario();
    $this->actingAs($admin)->put(route('admin.schedules.update', str_repeat('f', 24)), [...$payload, 'entry_key' => str_repeat('f', 24)])->assertNotFound();
    $other = IntramuralEdition::create(['name' => 'Other edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'draft']);
    $this->put(route('admin.schedules.update', $event['key']), [...$payload, 'edition_id' => $other->id])->assertNotFound();
    $edition->update(['status' => 'archived']);
    $this->get(route('admin.schedules.index'))->assertOk()->assertSee('No public timetable is available');
    $this->put(route('admin.schedules.update', $event['key']), $payload)->assertNotFound();
    $this->assertDatabaseCount('public_programmes', 0);
});
