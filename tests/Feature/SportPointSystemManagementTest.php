<?php

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionResult;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\EditionService;
use App\Services\PointSystemService;
use App\Services\StandingsService;
use App\Services\ThreePlaceScoringService;
use Illuminate\Support\Facades\DB;

function sportPointsFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Sport point management', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $index) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'SPORT-POINTS-'.$index, 'status' => 'active']));

    return [$admin, $edition, $teams];
}

function sportPointsEvent(IntramuralEdition $edition, string $code = 'VOLLEYBALL-MEN', string $name = 'Volleyball (Men)', string $system = 'sports_major'): EditionSport
{
    $sport = Sport::create(['name' => $name, 'code' => $code, 'participant_category' => 'male', 'status' => 'active', 'is_system' => true]);

    return $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => ['point_system' => $system, 'placements' => config('intramurals.point_systems.'.$system.'.placements')]]);
}

function sportPointsPayload($test, EditionSport $event, array $overrides = []): array
{
    $event = $event->fresh('sport');
    $page = $test->get(route('admin.sports.edit', ['sport' => $event->sport, 'edition_id' => $event->edition_id]))->assertOk();

    return [...[
        'edition_id' => $event->edition_id, 'name' => $event->sport->name, 'description' => $event->sport->description,
        'status' => $event->sport->status, 'participant_category' => $event->sport->participant_category,
        'participant_category_label' => $event->sport->getRawOriginal('participant_category_label'),
        'participant_type' => $event->participant_type, 'game_mechanic' => $event->game_mechanic,
        'configuration_status' => $event->status, 'rules' => $event->rules,
        'point_system' => app(PointSystemService::class)->keyFor($event), 'event_version' => $page->viewData('version'),
    ], ...$overrides];
}

function sportPointsSnapshot(): array
{
    return collect(['sports', 'edition_sports', 'sport_results', 'team_tallies', 'athlete_entries', 'competition_schedules', 'schedule_participants', 'competition_results', 'bracket_matches', 'bracket_competitors'])
        ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
}

test('admin adds a sport linked to current edited point values and Tabulator can score it', function () {
    [$admin, $edition, $teams] = sportPointsFixture();
    $source = sportPointsEvent($edition);
    $scoring = $source->scoring_rules;
    foreach ([31, 22, 16] as $index => $points) {
        $scoring['placements'][$index]['points'] = $points;
    }
    $source->update(['scoring_rules' => $scoring]);
    $this->actingAs($admin)->get(route('admin.sports.create', ['edition_id' => $edition->id]))->assertOk()
        ->assertSee('Linked point system')->assertSee('Scoring preview')->assertSee('data-sport-point-system', false);
    $this->post(route('admin.sports.store'), ['edition_id' => $edition->id, 'name' => 'New School Sport', 'status' => 'active', 'participant_category' => 'female', 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'configuration_status' => 'active', 'point_system' => 'sports_major'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.sports.index', ['edition_id' => $edition->id]));
    $event = EditionSport::whereHas('sport', fn ($query) => $query->where('name', 'New School Sport'))->sole();
    expect($event->sport->code)->toStartWith('SPORT-')
        ->and($event->sport->participant_category)->toBe('female')
        ->and($event->scoring_rules['point_system_override'])->toBeTrue()
        ->and(array_column($event->scoring_rules['placements'], 'points'))->toBe([31, 22, 16]);
    $this->get(route('admin.sports.index'))->assertOk()->assertSee('New School Sport')->assertSee('31.00 pts')->assertSee('Edit event &amp; points', false)->assertSee('Archive sport');
    $group = $this->get(route('admin.sports-points.index'))->assertOk()->viewData('pointSystems')->firstWhere('key', 'sports_major');
    expect($group['configured_count'])->toBe(2);
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']))
        ->get(route('tabulator.dashboard'))->assertOk()->assertSee('New School Sport');
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    expect((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(31.0);
    $this->assertDatabaseHas('audit_logs', ['action' => 'edition_sport.created', 'auditable_id' => $event->id]);
});

test('changing a declared sport point system requires a password and keeps winners brackets registrations and schedule history', function () {
    [$admin, $edition, $teams] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $other = sportPointsEvent($edition, 'CHESS-MEN', 'Chess (Men)', 'sports_minor');
    $entry = AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => Student::factory()->create()->id, 'team_id' => $teams[0]->id, 'status' => 'active']);
    $match = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'winner_team_id' => $teams[0]->id, 'status' => 'completed']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'bracket_match_id' => $match->id, 'title' => 'Sport schedule', 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'MPCC', 'status' => 'scheduled']);
    $standings = app(StandingsService::class);
    $result = $standings->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $admin);
    $otherResult = $standings->recordSportResult($other, [1 => $teams[1]->id], $admin);
    $original = $result->fresh()->getAttributes();
    $otherOriginal = $otherResult->fresh()->getAttributes();
    $histories = collect([$entry, $match, $schedule])->map->fresh()->map->getAttributes()->all();
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event, ['point_system' => 'sports_minor']);
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'name' => 'Failed Rename', 'admin_password' => 'wrong'])->assertSessionHasErrors('admin_password')->assertSessionMissing('_old_input.admin_password');
    expect(sportPointsSnapshot())->toBe($before);
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'admin_password' => 'password'])->assertSessionHasNoErrors();
    $after = $result->fresh()->getAttributes();
    $after['points_json'] = $original['points_json'];
    expect($after)->toBe($original)->and($otherResult->fresh()->getAttributes())->toBe($otherOriginal)
        ->and(array_column($result->fresh()->points_json, 'points'))->toBe([20, 15, 10])
        ->and(collect([$entry, $match, $schedule])->map->fresh()->map->getAttributes()->all())->toBe($histories)
        ->and((float) TeamTally::where('team_id', $teams[1]->id)->value('points'))->toBe(35.0);
    expect(app(\App\Services\EligibilityService::class)->slotFor($event->fresh()))->toBe('major');
    $beforeSync = $event->fresh()->scoring_rules;
    app(EditionService::class)->syncDefaultSports($edition);
    app(ThreePlaceScoringService::class)->sync();
    expect($event->fresh()->scoring_rules)->toBe($beforeSync);
});

test('sports follow only their selected point group after relinking and retain their sport classification', function () {
    [$admin, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $this->actingAs($admin)->put(route('admin.sports.update', $event->sport), sportPointsPayload($this, $event, ['point_system' => 'cultural_standard_10_7_5']))->assertSessionHasNoErrors();
    $groups = $this->get(route('admin.sports-points.index'))->viewData('pointSystems');
    expect($groups->firstWhere('key', 'sports_major')['configured_count'])->toBe(0)
        ->and($groups->firstWhere('key', 'cultural_standard_10_7_5')['configured_count'])->toBe(1);
    $this->put(route('admin.sports-points.update', 'sports_major'), ['systems' => ['sports_major' => ['admin_password' => 'password', 'placements' => [1 => ['label' => 'Champion', 'points' => 90, 'medal' => 'gold']]]]])->assertSessionHasNoErrors();
    expect((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(10.0);
    $this->put(route('admin.sports-points.update', 'cultural_standard_10_7_5'), ['systems' => ['cultural_standard_10_7_5' => ['admin_password' => 'password', 'placements' => [1 => ['label' => 'Champion', 'points' => 14, 'medal' => 'gold']]]]])->assertSessionHasNoErrors();
    expect((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(14.0)
        ->and(app(StandingsService::class)->allowsRepeatedWinnersFor($event->fresh()))->toBeFalse();
    expect(app(\App\Services\AthleteEntryService::class)->selectableSportsFor($edition)->pluck('id')->all())->toContain($event->id)
        ->and(app(\App\Services\EligibilityService::class)->slotFor($event->fresh()))->toBe('major');
    $this->get(route('admin.sports.index', ['point_system' => 'cultural_standard_10_7_5']))->assertOk()->assertSee('Volleyball (Men)');
    $this->get(route('admin.cultural.index'))->assertOk()->assertDontSee('Volleyball (Men)');
});

test('renaming a sport preserves custom unlinked rules and does not recalculate declared points', function () {
    [$admin, $edition, $teams] = sportPointsFixture();
    $event = sportPointsEvent($edition, 'SPORT-LEGACY', 'Legacy Custom Sport');
    $event->update(['scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Winner', 'points' => 42, 'medal' => 'gold']]]]);
    $result = app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin);
    $original = $result->fresh()->getAttributes();
    $tallies = DB::table('team_tallies')->get()->toJson();
    $this->actingAs($admin)->put(route('admin.sports.update', $event->sport), sportPointsPayload($this, $event, ['name' => 'Renamed Custom Sport']))->assertSessionHasNoErrors();
    expect($result->fresh()->getAttributes())->toBe($original)->and(DB::table('team_tallies')->get()->toJson())->toBe($tallies);
});

test('new sports stay available to register when linked to a cultural point system', function () {
    [$admin, $edition] = sportPointsFixture();
    $this->actingAs($admin)->post(route('admin.sports.store'), ['edition_id' => $edition->id, 'name' => 'Custom Team Sport', 'status' => 'active', 'participant_category' => 'open', 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'configuration_status' => 'active', 'point_system' => 'cultural_standard_10_7_5'])->assertSessionHasNoErrors();
    $event = $edition->editionSports()->sole();
    expect(app(\App\Services\AthleteEntryService::class)->selectableSportsFor($edition)->pluck('id')->all())->toContain($event->id)
        ->and(app(\App\Services\EligibilityService::class)->slotFor($event))->toBe('major');
});

test('a sport point system cannot discard declared places or hide a declared event', function () {
    [$admin, $edition, $teams] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id], $admin);
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event);
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'point_system' => 'cultural_special_2', 'admin_password' => 'password'])->assertSessionHasErrors('point_system');
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'status' => 'inactive'])->assertSessionHasErrors('status');
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'configuration_status' => 'archived'])->assertSessionHasErrors('configuration_status');
    expect(sportPointsSnapshot())->toBe($before);
});

test('sports archive and restore is scoped to one edition and keeps registrations brackets and schedules', function () {
    [$admin, $edition, $teams] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $otherEdition = IntramuralEdition::create(['name' => 'Prior edition', 'school_year' => '2025-2026', 'starts_on' => '2025-10-19', 'ends_on' => '2025-10-23', 'status' => 'archived']);
    $otherConfiguration = $otherEdition->editionSports()->create(['sport_id' => $event->sport_id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => Student::factory()->create()->id, 'team_id' => $teams[0]->id, 'status' => 'active']);
    BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'pending']);
    CompetitionSchedule::create(['edition_sport_id' => $event->id, 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'MPCC', 'status' => 'scheduled']);
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event);
    $before = sportPointsSnapshot();
    $this->delete(route('admin.sports.destroy', $event->sport), ['edition_id' => $edition->id, 'event_version' => $payload['event_version']])->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe('archived')->and($event->sport->fresh()->trashed())->toBeFalse()->and($otherConfiguration->fresh()->status)->toBe('active');
    $after = sportPointsSnapshot();
    unset($before['edition_sports'], $after['edition_sports']);
    expect($after)->toBe($before)->and(app(StandingsService::class)->scoringEventsFor($edition))->toHaveCount(0);
    $this->get(route('admin.sports.index'))->assertOk()->assertDontSee('Volleyball (Men)');
    $page = $this->get(route('admin.sports.index', ['status' => 'archived', 'point_system' => 'sports_major']))->assertOk()->assertSee('Volleyball (Men)')->assertSee('Restore sport');
    $source = sportPointsEvent($edition, 'BASEBALL', 'Baseball');
    $rules = $source->scoring_rules;
    $rules['placements'][0]['points'] = 36;
    $source->update(['scoring_rules' => $rules]);
    $this->post(route('admin.sports.restore', $event->sport_id), ['edition_id' => $edition->id, 'event_version' => $page->viewData('versions')[$event->sport_id]])->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe('preparation')->and((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(36.0);
    $this->assertDatabaseHas('audit_logs', ['action' => 'edition_sport.archived', 'auditable_id' => $event->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'edition_sport.restored', 'auditable_id' => $event->id]);
});

test('sports with any recorded result cannot be archived', function (string $source) {
    [$admin, $edition, $teams] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    if ($source === 'standings') {
        app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin);
    } elseif ($source === 'bracket') {
        BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'completed', 'winner_team_id' => $teams[0]->id]);
    } else {
        $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'MPCC', 'status' => 'scheduled']);
        CompetitionResult::create(['competition_schedule_id' => $schedule->id, 'result_data' => ['winner' => $teams[0]->id], 'submitted_by' => $admin->id, 'submitted_at' => now(), 'status' => 'submitted']);
    }
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event);
    $before = sportPointsSnapshot();
    $this->delete(route('admin.sports.destroy', $event->sport), ['edition_id' => $edition->id, 'event_version' => $payload['event_version']])->assertSessionHasErrors('event');
    expect(sportPointsSnapshot())->toBe($before);
})->with(['standings', 'bracket', 'schedule']);

test('stale sport forms invalid mechanics and invalid point systems cannot partially change records', function () {
    [$admin, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event, ['name' => 'Must Not Be Saved']);
    $event->update(['rules' => 'Another administrator changed this']);
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $event->sport), $payload)->assertSessionHasErrors('event_version');
    $this->delete(route('admin.sports.destroy', $event->sport), ['edition_id' => $edition->id, 'event_version' => $payload['event_version']])->assertSessionHasErrors('event_version');
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'game_mechanic' => 'invalid'])->assertSessionHasErrors('game_mechanic');
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'point_system' => 'unknown'])->assertSessionHasErrors('point_system');
    $create = [...$payload, 'name' => 'Invalid new sport', 'point_system' => 'unknown'];
    $this->post(route('admin.sports.store'), $create)->assertSessionHasErrors('point_system');
    expect(sportPointsSnapshot())->toBe($before);
});

test('sport forms cannot silently change an existing bracket format and track and field stays custom', function () {
    [$admin, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'pending']);
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event, ['game_mechanic' => 'single_elimination']);
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $event->sport), $payload)->assertSessionHasErrors('game_mechanic');
    expect(sportPointsSnapshot())->toBe($before);
    $run = sportPointsEvent($edition, '100M-RUN-MEN', '100m Run (Men)', 'sports_athletics');
    $this->put(route('admin.sports.update', $run->sport), sportPointsPayload($this, $run, ['game_mechanic' => 'round_robin']))->assertSessionHasNoErrors();
    expect($run->fresh()->game_mechanic)->toBe('custom');
});

test('legacy archived sports can be restored without rewriting their records', function () {
    [$admin, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $sport = $event->sport;
    $sport->delete();
    $this->actingAs($admin);
    $page = $this->get(route('admin.sports.index', ['status' => 'archived']))->assertOk()->assertSee('Volleyball (Men)')->assertSee('Restore sport');
    $this->post(route('admin.sports.restore', $sport->id), ['edition_id' => $edition->id, 'event_version' => $page->viewData('versions')[$sport->id]])->assertSessionHasNoErrors();
    expect($sport->fresh()->trashed())->toBeFalse()->and($event->fresh()->status)->toBe('preparation');
});

test('sports CRUD rejects archived editions cross edition versions and cultural records', function () {
    [$admin, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $cultural = sportPointsEvent($edition, 'CULT-CUSTOM-OTHER', 'Cultural Contest');
    $this->actingAs($admin);
    $payload = sportPointsPayload($this, $event);
    $this->get(route('admin.sports.edit', ['sport' => $cultural->sport, 'edition_id' => $edition->id]))->assertRedirect(route('admin.cultural.edit', $cultural));
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $cultural->sport), [...$payload, 'name' => $cultural->sport->name])->assertNotFound();
    $this->delete(route('admin.sports.destroy', $cultural->sport), ['edition_id' => $edition->id, 'event_version' => $payload['event_version']])->assertNotFound();
    expect(sportPointsSnapshot())->toBe($before);
    $otherEdition = IntramuralEdition::create(['name' => 'Second edition', 'school_year' => '2027-2028', 'starts_on' => '2027-10-19', 'ends_on' => '2027-10-23', 'status' => 'draft']);
    $otherEdition->editionSports()->create(['sport_id' => $event->sport_id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $this->put(route('admin.sports.update', $event->sport), [...$payload, 'edition_id' => $otherEdition->id])->assertSessionHasErrors('event_version');
    $edition->update(['status' => 'archived']);
    $before = sportPointsSnapshot();
    $this->put(route('admin.sports.update', $event->sport), $payload)->assertSessionHasErrors('edition_id');
    $this->post(route('admin.sports.store'), [...$payload, 'name' => 'Archived new sport'])->assertSessionHasErrors('edition_id');
    expect(sportPointsSnapshot())->toBe($before);
});

test('only administrators can manage sports and linked point systems', function (string $role) {
    [, $edition] = sportPointsFixture();
    $event = sportPointsEvent($edition);
    $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
    $this->get(route('admin.sports.create'))->assertForbidden();
    $this->get(route('admin.sports.edit', $event->sport))->assertForbidden();
    $this->post(route('admin.sports.store'), [])->assertForbidden();
    $this->put(route('admin.sports.update', $event->sport), [])->assertForbidden();
    $this->delete(route('admin.sports.destroy', $event->sport), [])->assertForbidden();
    $this->post(route('admin.sports.restore', $event->sport_id), [])->assertForbidden();
})->with(['gam', 'tabulator', 'coordinator']);
