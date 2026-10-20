<?php

use App\Models\IntramuralEdition;
use App\Models\PublicProgramme;
use App\Models\User;
use App\Services\PublicProgrammeService;
use App\Services\PublicScheduleService;
use Illuminate\Support\Facades\DB;

function programmeGamesScenario(): array
{
    $edition = IntramuralEdition::create(['name' => config('intramurals.default_edition.name'), 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $event = app(PublicProgrammeService::class)->eventsFor($edition)->firstWhere('cells', 'E12:E15');
    $payload = array_intersect_key($event, array_flip(['event', 'date', 'session', 'starts_at', 'category', 'venue', 'version', 'is_published', 'games']));

    return [$edition, $admin, $event, [...$payload, 'entry_key' => $event['key'], 'edition_id' => $edition->id, 'ends_at' => null, 'games_present' => 1]];
}

test('the supplied game file supplies all confirmed times venues and three game series with logos', function () {
    [$edition, $admin] = programmeGamesScenario();
    $events = app(PublicProgrammeService::class)->eventsFor($edition)->keyBy('cells');
    foreach ([
        'E12:E15' => ['2026-10-21', '12:00', 'MPC'], 'F15' => ['2026-10-21', '12:00', 'SSC'],
        'F12' => ['2026-10-21', '13:00', 'Open Field'], 'F13' => ['2026-10-21', '13:00', 'Open Field'], 'F14' => ['2026-10-21', '13:00', 'Open Field'],
        'I6' => ['2026-10-22', '08:00', 'CompLab'], 'G6:G9' => ['2026-10-22', '08:00', 'MPC'], 'H6:H8' => ['2026-10-22', '08:00', 'Open Field'], 'C12:C13' => ['2026-10-22', '13:00', 'Baseball Diamond'],
        'J6' => ['2026-10-23', '07:00', 'MPC'], 'J7' => ['2026-10-23', '09:00', 'MPC'], 'J8' => ['2026-10-23', '11:00', 'MPC'], 'J12' => ['2026-10-23', '13:00', 'MPC'],
    ] as $cells => [$date, $time, $venue]) {
        expect($events[$cells])->toMatchArray(['date' => $date, 'starts_at' => $time, 'venue' => $venue]);
    }
    expect($events['J8']['session'])->toBe('AM')->and($events['J12']['event'])->toBe('Modified Volleyball (Employee)')
        ->and($events['G12:G14']['is_published'])->toBeFalse()->and($events['H12:H14']['is_published'])->toBeFalse();
    $schedules = app(PublicScheduleService::class)->today('2026-10-21')['todaySchedules'];
    $volleyball = $schedules->firstWhere('event', 'Volleyball M/W');
    expect($volleyball['matchups'])->toHaveCount(3)
        ->and($volleyball['matchups'][0]['home'])->toMatchArray(['name' => 'Terraquatic Eagles', 'logo_path' => 'images/logo/terraquatic_eagles.png'])
        ->and($volleyball['matchups'][0]['away']['name'])->toBe('Sea Dragons')
        ->and($volleyball['matchups'][1]['home']['name'])->toBe('Loser of Game 1')
        ->and($volleyball['matchups'][2]['home']['name'])->toBe('Winner of Game 1')
        ->and($volleyball['matchups'][2]['away']['logo_path'])->toBe('images/logo/trojan_warriors.png');
    $this->actingAs($admin)->get(route('admin.schedules.index', ['date' => '2026-10-21']))->assertOk()
        ->assertSee('Games / matchups')->assertSee('Add game')->assertSee('games[0][home]', false)->assertSee('Remove game')->assertSee('programme-matchups');
    $this->get(route('public.schedule', ['date' => '2026-10-21']))->assertOk()->assertSee('Loser of Game 1')->assertSee('Winner of Game 1')->assertSee('programme-matchups');
});

test('admins edit add and clear game matchups without changing any saved sporting records', function () {
    [$edition, $admin, $event, $payload] = programmeGamesScenario();
    $tables = ['students', 'team_members', 'athlete_entries', 'sports', 'edition_sports', 'competition_schedules', 'bracket_matches', 'bracket_competitors', 'competition_results', 'sport_results', 'team_tallies'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()])->all();
    $before = $snapshot();
    $games = [
        ['home' => 'team:trojan-warriors', 'away' => 'team:sea-dragons'],
        ['home' => 'winner:1', 'away' => 'team:terraquatic-eagles'],
    ];
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), [...$payload, 'games' => $games])->assertSessionHasNoErrors();
    $saved = app(PublicProgrammeService::class)->eventsFor($edition)->firstWhere('key', $event['key']);
    expect($saved['games'])->toEqual($games)->and($snapshot())->toBe($before);
    $public = app(PublicScheduleService::class)->today('2026-10-21')['todaySchedules']->firstWhere('event', $event['event']);
    expect($public['matchups'])->toHaveCount(2)->and($public['matchups'][0]['home']['logo_path'])->toBe('images/logo/trojan_warriors.png');
    $legacy = $payload;
    unset($legacy['games'], $legacy['games_present']);
    $this->put(route('admin.schedules.update', $event['key']), [...$legacy, 'version' => $saved['version'], 'venue' => 'SSC'])->assertSessionHasNoErrors();
    $saved = app(PublicProgrammeService::class)->eventsFor($edition)->firstWhere('key', $event['key']);
    expect($saved['games'])->toEqual($games);
    $clear = $payload;
    unset($clear['games']);
    $this->put(route('admin.schedules.update', $event['key']), [...$clear, 'version' => $saved['version']])->assertSessionHasNoErrors();
    expect(app(PublicProgrammeService::class)->eventsFor($edition)->firstWhere('key', $event['key'])['games'])->toBe([])
        ->and($snapshot())->toBe($before);
    $this->assertDatabaseHas('audit_logs', ['action' => 'public_schedule.updated']);
});

test('invalid game opponents and references return editable validation errors', function (array $games) {
    [, $admin, $event, $payload] = programmeGamesScenario();
    $this->actingAs($admin)->put(route('admin.schedules.update', $event['key']), [...$payload, 'games' => $games])->assertSessionHasErrors();
    $this->get(route('admin.schedules.index'))->assertOk();
    $this->assertDatabaseCount('public_programmes', 0);
})->with([
    [[['home' => 'winner:1', 'away' => 'team:sea-dragons']]],
    [[['home' => 'team:sea-dragons', 'away' => 'team:sea-dragons']]],
    [[['home' => 'team:unknown', 'away' => 'team:sea-dragons']]],
    [[['home' => ['invalid'], 'away' => 'team:sea-dragons']]],
    [[['invalid' => 'not an opponent']]],
    [array_fill(0, 13, ['home' => null, 'away' => null])],
]);

test('the one time file amendment preserves unrelated admin changes and never replays over later edits', function () {
    [$edition] = programmeGamesScenario();
    $base = collect(config('public_programme.events'))->map(fn ($event, $index) => [...$event, 'key' => substr(hash('sha256', $event['cells']), 0, 24), 'category' => $event['category'] ?? 'sports', 'is_published' => true, 'version' => 4, 'position' => $index]);
    $base[0] = [...$base[0], 'event' => 'Admin custom opening', 'venue' => 'Keep this venue'];
    $base = $base->map(fn ($event) => $event['cells'] === 'E12:E15' ? [...$event, 'ends_at' => '10:00'] : $event);
    $programme = PublicProgramme::create(['edition_id' => $edition->id, 'events' => $base->all(), 'source' => config('public_programme.source')]);
    $service = app(PublicProgrammeService::class);
    $protected = ['students', 'athlete_entries', 'sports', 'edition_sports', 'bracket_matches', 'sport_results', 'team_tallies', 'competition_schedules'];
    $snapshot = fn () => collect($protected)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()])->all();
    $before = $snapshot();
    expect($service->importGameSchedule())->toBe(1)->and($snapshot())->toBe($before);
    $events = collect($programme->fresh()->events)->keyBy('cells');
    expect($events['A6:A9'])->toMatchArray(['event' => 'Admin custom opening', 'venue' => 'Keep this venue', 'version' => 4])
        ->and($events['E12:E15'])->toMatchArray(['starts_at' => '12:00', 'venue' => 'MPC', 'version' => 5, 'ends_at' => null]);
    $edited = collect($programme->fresh()->events)->map(fn ($event) => $event['cells'] === 'E12:E15' ? [...$event, 'venue' => 'Admin changed after import'] : $event)->all();
    $programme->update(['events' => $edited]);
    expect($service->importGameSchedule())->toBe(0)->and($programme->fresh()->events)->toBe($edited)->and($snapshot())->toBe($before);
    $this->assertDatabaseHas('audit_logs', ['action' => 'public_schedule.file_updated']);
});
