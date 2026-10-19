<?php

use App\Models\AthleteEntry;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\PublicScheduleService;
use Illuminate\Support\Str;

function scheduleCreationEvent(string $code = 'BASKET-5X5-MEN', string $type = 'team', ?IntramuralEdition $edition = null): EditionSport
{
    $edition ??= IntramuralEdition::create([
        'name' => 'Schedule creation edition', 'school_year' => '2026-2027',
        'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active',
    ]);
    $sport = Sport::create([
        'name' => $code, 'code' => $code, 'participant_category' => str_ends_with($code, '-MEN') ? 'male' : 'open', 'status' => 'active',
    ]);

    return EditionSport::create([
        'edition_id' => $edition->id, 'sport_id' => $sport->id,
        'participant_type' => $type, 'game_mechanic' => $sport->isTrackAndField() ? 'custom' : 'round_robin', 'status' => 'active',
    ]);
}

function scheduleCreationData(EditionSport $event, array $overrides = []): array
{
    return array_replace([
        'edition_id' => $event->edition_id, 'edition_sport_id' => $event->id, 'title' => 'Finals',
        'starts_at' => '2026-10-20T14:35', 'ends_at' => '2026-10-20T16:10', 'status' => 'scheduled',
    ], $overrides);
}

function scheduleCreationEntry(EditionSport $event, ?Team $team = null, array $overrides = []): AthleteEntry
{
    return AthleteEntry::create(array_replace([
        'edition_sport_id' => $event->id, 'student_id' => Student::factory()->create(['status' => 'active', 'gender' => 'Male'])->id,
        'team_id' => $team?->id, 'status' => 'active',
    ], $overrides));
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
});

test('live competition links to a usable schedule creation form', function () {
    $event = scheduleCreationEvent();
    $this->get(route('admin.competition.index'))->assertOk()->assertSee(route('admin.competition.create'));
    $this->get(route('admin.competition.create'))->assertOk()
        ->assertSee('Add a schedule')->assertSee('datetime-local', false)->assertSee($event->sport->name)
        ->assertSee('Search participants')->assertSee(route('admin.competition.schedules.store'));
});

test('administrators add a schedule with exact times registered teams facilitator and audit trail', function () {
    $event = scheduleCreationEvent();
    $teams = collect(['Sea Dragons', 'Trojan Warriors'])->map(fn ($name) => Team::create([
        'edition_id' => $event->edition_id, 'name' => $name, 'code' => Str::slug($name), 'status' => 'active',
    ]));
    $entries = $teams->map(fn ($team) => scheduleCreationEntry($event, $team));
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $existing = CompetitionSchedule::create([
        'edition_sport_id' => $event->id, 'title' => 'Existing game', 'starts_at' => '2026-10-19 08:00', 'ends_at' => '2026-10-19 10:00', 'status' => 'completed',
    ]);
    $before = $existing->fresh()->getAttributes();

    $response = $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, [
        'participants' => $teams->map(fn ($team) => 'team:'.$team->id)->all(), 'coordinator_id' => $coordinator->id,
    ]))->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::where('title', 'Finals')->firstOrFail();
    $response->assertRedirect(route('admin.competition.index', ['page' => 1]).'#schedule-'.$schedule->id);
    expect($schedule->starts_at->format('Y-m-d H:i'))->toBe('2026-10-20 14:35')
        ->and($schedule->ends_at->format('H:i'))->toBe('16:10')
        ->and($schedule->venue)->toBe('MPCC')->and($schedule->coordinator_id)->toBe($coordinator->id)
        ->and($schedule->bracket_match_id)->toBeNull()
        ->and($schedule->participants->pluck('team_id')->sort()->values()->all())->toBe($teams->pluck('id')->sort()->values()->all())
        ->and($existing->fresh()->getAttributes())->toBe($before);
    foreach ($entries as $entry) {
        $this->assertDatabaseHas('athlete_entries', ['id' => $entry->id, 'status' => 'active']);
    }
    $this->assertDatabaseHas('audit_logs', ['action' => 'competition_schedule.created', 'auditable_id' => $schedule->id]);
    $public = app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules']->sole();
    expect($public['time'])->toBe('2:35 PM')->and($public['venue'])->toBe('MPCC')
        ->and($public['facilitator'])->toBe($coordinator->name)->and($public['competitor_teams'])->toHaveCount(2);
});

test('track and field schedules select registered athletes and default to the oval without generating brackets', function () {
    $event = scheduleCreationEvent('100M-RUN-MEN', 'individual');
    $entry = scheduleCreationEntry($event);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, ['participants' => ['entry:'.$entry->id]]))
        ->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::sole();
    expect($schedule->venue)->toBe('Field/Oval')->and($schedule->participants->sole()->athlete_entry_id)->toBe($entry->id);
    $this->assertDatabaseCount('bracket_matches', 0);
    $this->assertDatabaseCount('sport_results', 0);
});

test('selecting a registered doubles pair includes both players', function () {
    $event = scheduleCreationEvent('BADMINTON-DOUBLES-MEN', 'dual');
    $pairKey = (string) Str::uuid();
    $one = scheduleCreationEntry($event, null, ['pair_key' => $pairKey]);
    $two = scheduleCreationEntry($event, null, ['pair_key' => $pairKey]);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, ['participants' => ['pair:'.$pairKey]]))
        ->assertSessionHasNoErrors();
    expect(CompetitionSchedule::sole()->participants->pluck('athlete_entry_id')->sort()->values()->all())->toBe([$one->id, $two->id]);
});

test('cultural schedules include every active faction even without cultural sport registrations', function () {
    $event = scheduleCreationEvent('CULT-DIGITAL-POSTER');
    foreach (['Sea Dragons', 'Trojan Warriors', 'Terraquatic Eagles'] as $name) {
        Team::create(['edition_id' => $event->edition_id, 'name' => $name, 'code' => Str::slug($name), 'status' => 'active']);
    }
    Team::create(['edition_id' => $event->edition_id, 'name' => 'Inactive', 'code' => 'INACTIVE', 'status' => 'inactive']);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event))->assertSessionHasNoErrors();
    expect(CompetitionSchedule::sole()->participants)->toHaveCount(3);
    expect(app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules']->sole()['category'])->toBe('cultural');
    $this->assertDatabaseCount('athlete_entries', 0);
});

test('school activities require a title and appear separately from sports', function () {
    $event = scheduleCreationEvent('CULT-FESTIVAL-PROGRAM');
    Team::create(['edition_id' => $event->edition_id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, ['title' => '   ']))->assertSessionHasErrors('title');
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, ['title' => 'Ruperto Run 2026', 'venue' => 'Field/Oval']))
        ->assertSessionHasNoErrors();
    expect(CompetitionSchedule::sole()->is_activity)->toBeTrue()
        ->and(app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules']->sole())
        ->toMatchArray(['event' => 'Ruperto Run 2026', 'category' => 'activities', 'sport_group' => null, 'competitor_teams' => []]);
    $this->assertDatabaseCount('athlete_entries', 0);
});

test('editing a new schedule requires an end time instead of failing a database constraint', function () {
    $event = scheduleCreationEvent();
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event))->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::sole();
    $this->put(route('admin.competition.schedules.update', $schedule), scheduleCreationData($event, ['ends_at' => '', 'venue' => 'MPCC']))
        ->assertSessionHasErrors('ends_at');
    expect($schedule->fresh()->ends_at->format('H:i'))->toBe('16:10');
});

test('invalid dates times venues and facilitators do not save any schedule', function (array $overrides, string $field) {
    $event = scheduleCreationEvent();
    $this->from(route('admin.competition.create'))->post(route('admin.competition.schedules.store'), scheduleCreationData($event, $overrides))
        ->assertRedirect(route('admin.competition.create'))->assertSessionHasErrors($field)->assertSessionHasInput('title', 'Finals');
    $this->assertDatabaseCount('competition_schedules', 0);
    $this->assertDatabaseCount('schedule_participants', 0);
})->with([
    'before edition' => [['starts_at' => '2026-10-18T14:35'], 'starts_at'],
    'after edition' => [['ends_at' => '2026-10-24T16:10'], 'ends_at'],
    'end before start' => [['ends_at' => '2026-10-20T14:00'], 'ends_at'],
    'no end' => [['ends_at' => ''], 'ends_at'],
    'invalid venue' => [['venue' => 'Not a venue'], 'venue'],
    'invalid facilitator' => [['coordinator_id' => 99999], 'coordinator_id'],
]);

test('participants from another event withdrawn entries and wrong genders are rejected', function (string $kind) {
    $event = scheduleCreationEvent('100M-RUN-MEN', 'individual');
    $other = scheduleCreationEvent('200M-RUN-MEN', 'individual', $event->edition);
    $entry = scheduleCreationEntry($kind === 'other' ? $other : $event, null, $kind === 'withdrawn' ? ['status' => 'withdrawn'] : []);
    if ($kind === 'gender') {
        $entry->student->update(['gender' => 'Female']);
    }
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event, ['participants' => ['entry:'.$entry->id]]))
        ->assertSessionHasErrors('participants');
    $this->assertDatabaseCount('competition_schedules', 0);
})->with(['other', 'withdrawn', 'gender']);

test('only available current events in the chosen edition can be scheduled', function () {
    $open = scheduleCreationEvent('BASKET-5X5');
    $men = scheduleCreationEvent('BASKET-5X5-MEN', 'team', $open->edition);
    $this->get(route('admin.competition.create'))->assertOk()->assertViewHas('events', fn ($events) => $events->modelKeys() === [$men->id]);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($open))->assertSessionHasErrors('edition_sport_id');
    $men->update(['status' => 'archived']);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($men))->assertSessionHasErrors('edition_sport_id');
    $men->edition->update(['status' => 'closed']);
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($men))->assertSessionHasErrors('edition_id');
    $this->assertDatabaseCount('competition_schedules', 0);
});

test('sports can be scheduled before participants are announced', function () {
    $event = scheduleCreationEvent();
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event))->assertSessionHasNoErrors();
    expect(CompetitionSchedule::sole()->participants)->toBeEmpty();
    expect(app(PublicScheduleService::class)->today('2026-10-20')['todaySchedules']->sole()['competitors'])->toBe('To be announced');
});

test('new schedule redirect opens the correct page when many schedules already exist', function () {
    $event = scheduleCreationEvent();
    foreach (range(1, 21) as $number) {
        CompetitionSchedule::create([
            'edition_sport_id' => $event->id, 'starts_at' => '2026-10-19 08:00', 'ends_at' => '2026-10-19 10:00', 'status' => 'scheduled',
        ]);
    }
    $response = $this->from(route('admin.competition.create'))
        ->post(route('admin.competition.schedules.store'), scheduleCreationData($event))->assertSessionHasNoErrors();
    $schedule = CompetitionSchedule::where('title', 'Finals')->sole();
    $response->assertRedirect(route('admin.competition.index', ['page' => 2]).'#schedule-'.$schedule->id);
    $this->get(route('admin.competition.index', ['page' => 2]))->assertOk()->assertSee('id="schedule-'.$schedule->id.'"', false);
});

test('schedule creation handles editions with no configured events and no editions', function () {
    $this->get(route('admin.competition.create'))->assertOk()->assertSee('Create a draft or active edition');
    $event = scheduleCreationEvent();
    $event->delete();
    $this->get(route('admin.competition.create'))->assertOk()->assertSee('No available events');
});

test('schedule creation is restricted to administrators', function (string $role) {
    $event = scheduleCreationEvent();
    $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
    $this->get(route('admin.competition.create'))->assertForbidden();
    $this->post(route('admin.competition.schedules.store'), scheduleCreationData($event))->assertForbidden();
    $this->assertDatabaseCount('competition_schedules', 0);
})->with(['gam', 'tabulator', 'coordinator']);
