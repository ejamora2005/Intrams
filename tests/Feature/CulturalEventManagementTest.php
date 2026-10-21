<?php

use App\Models\AthleteEntry;
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
use Illuminate\Validation\ValidationException;

function culturalCrudFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Cultural CRUD edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $index) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'CULT-CRUD-'.$index, 'status' => 'active']));

    return [$admin, $edition, $teams];
}

function culturalCrudEvent(IntramuralEdition $edition, string $code = 'CULT-FEST-DANCE', string $name = 'Festival Dance', string $system = 'cultural_festival_dance'): EditionSport
{
    $sport = Sport::create(['name' => $name, 'code' => $code, 'status' => 'active', 'is_system' => true]);

    return $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active', 'scoring_rules' => ['point_system' => $system, 'placements' => config('intramurals.point_systems.'.$system.'.placements')]]);
}

function culturalCrudPayload($test, EditionSport $event, array $overrides = []): array
{
    $version = $test->get(route('admin.cultural.edit', $event))->assertOk()->viewData('version');

    return [...[
        'edition_id' => $event->edition_id, 'name' => $event->sport->name, 'description' => $event->sport->description,
        'status' => $event->status, 'participant_type' => $event->participant_type, 'rules' => $event->rules,
        'point_system' => app(PointSystemService::class)->keyFor($event), 'event_version' => $version,
    ], ...$overrides];
}

function culturalCrudSnapshot(): array
{
    return collect(['sports', 'edition_sports', 'sport_results', 'team_tallies', 'athlete_entries', 'competition_schedules', 'schedule_participants'])
        ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
}

test('admin creates a cultural event using current point values and Tabulator can declare its winners', function () {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $source = culturalCrudEvent($edition, 'CULT-QUIZ', 'Existing Quiz', 'cultural_major_15_10_7');
    $rules = $source->scoring_rules;
    foreach ([17, 11, 8] as $index => $points) {
        $rules['placements'][$index]['points'] = $points;
    }
    $source->update(['scoring_rules' => $rules]);
    $this->actingAs($admin)->get(route('admin.cultural.create', ['edition_id' => $edition->id]))
        ->assertOk()->assertSee('Linked point system')->assertSee('Scoring preview')->assertSee('Sports Major Events');
    $this->post(route('admin.cultural.store'), ['edition_id' => $edition->id, 'name' => 'New Cultural Contest', 'description' => 'School contest', 'participant_type' => 'individual', 'status' => 'active', 'point_system' => 'cultural_major_15_10_7'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.cultural.index', ['edition_id' => $edition->id]));
    $event = EditionSport::whereHas('sport', fn ($q) => $q->where('name', 'New Cultural Contest'))->with('sport')->sole();
    expect($event->sport->code)->toStartWith('CULT-CUSTOM-')
        ->and($event->scoring_rules['point_system_override'])->toBeTrue()
        ->and(array_column($event->scoring_rules['placements'], 'points'))->toBe([17, 11, 8]);
    $this->get(route('admin.cultural.index'))->assertOk()->assertSee('New Cultural Contest')->assertSee(route('admin.cultural.edit', $event), false);
    $groups = $this->get(route('admin.sports-points.index'))->assertOk()->viewData('pointSystems');
    expect(collect($groups->firstWhere('key', 'cultural_major_15_10_7')['configured_items'])->pluck('name')->all())->toContain('New Cultural Contest');
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $this->actingAs($tabulator)->get(route('tabulator.dashboard', ['category' => 'cultural']))->assertOk()->assertSee('New Cultural Contest');
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], 'tabulator_password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect(array_column($event->sportResult()->sole()->points_json, 'points'))->toBe([17, 11, 8])
        ->and((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(17.0);
    $this->assertDatabaseHas('audit_logs', ['action' => 'request.admin.cultural.store', 'outcome' => 'success']);
});

test('an existing cultural event can move between point systems and only its selected group updates it', function () {
    [$admin, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    culturalCrudEvent($edition, 'CULT-SECOND-DANCE', 'Another Dance');
    $this->actingAs($admin);
    $payload = culturalCrudPayload($this, $event, ['point_system' => 'cultural_standard_10_7_5']);
    $this->put(route('admin.cultural.update', $event), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $groups = $this->get(route('admin.sports-points.index'))->viewData('pointSystems');
    expect($groups->firstWhere('key', 'cultural_festival_dance')['configured_count'])->toBe(1)
        ->and($groups->firstWhere('key', 'cultural_standard_10_7_5')['configured_count'])->toBe(1);
    $this->put(route('admin.sports-points.update', 'cultural_festival_dance'), ['systems' => ['cultural_festival_dance' => ['admin_password' => 'password', 'placements' => [1 => ['label' => 'Champion', 'points' => 99, 'medal' => 'gold']]]]])
        ->assertSessionHasNoErrors();
    expect((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(10.0);
    $this->put(route('admin.sports-points.update', 'cultural_standard_10_7_5'), ['systems' => ['cultural_standard_10_7_5' => ['admin_password' => 'password', 'placements' => [1 => ['label' => 'Champion', 'points' => 13, 'medal' => 'gold']]]]])
        ->assertSessionHasNoErrors();
    expect((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(13.0);
    $before = $event->fresh()->scoring_rules;
    app(EditionService::class)->syncDefaultSports($edition);
    app(ThreePlaceScoringService::class)->sync();
    expect($event->fresh()->scoring_rules)->toBe($before);
});

test('custom cultural events allow repeating factions regardless of their linked point system', function (string $system) {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $this->actingAs($admin)->post(route('admin.cultural.store'), [
        'edition_id' => $edition->id, 'name' => 'Custom Cultural Awards', 'participant_type' => 'individual',
        'status' => 'active', 'point_system' => $system,
    ])->assertSessionHasNoErrors();
    $event = EditionSport::whereHas('sport', fn ($query) => $query->where('name', 'Custom Cultural Awards'))->sole();
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $placements = [1 => $teams[0]->id, 2 => $teams[0]->id, 3 => $teams[0]->id];
    $this->actingAs($tabulator)->get(route('tabulator.dashboard', ['edition_sport_id' => $event->id]))
        ->assertOk()->assertViewHas('allowRepeatedWinners', true)->assertSee('data-allow-repeated-winners="true"', false);
    $this->post(route('tabulator.sport-results.store'), [
        'edition_sport_id' => $event->id, 'placements' => $placements, 'tabulator_password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $rules = app(StandingsService::class)->pointRulesFor($event);
    $tally = TeamTally::where('team_id', $teams[0]->id)->sole();
    expect($event->sportResult()->sole()->placements_json)->toBe($placements)
        ->and((float) $tally->points)->toBe((float) array_sum(array_column($rules, 'points')))
        ->and([$tally->gold_count, $tally->silver_count, $tally->bronze_count])->toBe([1, 1, 1]);
    $public = app(StandingsService::class)->declaredResultsFor($edition)->firstWhere('id', $event->id);
    expect($public['category'])->toBe('cultural')
        ->and($public['placements']->pluck('team_name')->all())->toBe(array_fill(0, 3, $teams[0]->name))
        ->and($public['placements']->pluck('points')->all())->toBe(array_column($rules, 'points'));
    $savedPage = $this->get(route('tabulator.dashboard', ['edition_sport_id' => $event->id]))
        ->assertOk()->assertSee('Editing declared results')->assertViewHas('allowRepeatedWinners', true);
    expect(substr_count($savedPage->getContent(), '<option value="'.$teams[0]->id.'" selected>'))->toBe(3);
})->with(['cultural points' => 'cultural_standard_10_7_5', 'sports points' => 'sports_major']);

test('repeated cultural placements still reject inactive factions and factions from another edition', function () {
    [, $edition, $teams] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $teams[1]->update(['status' => 'inactive']);
    $otherEdition = IntramuralEdition::create(['name' => 'Other edition', 'school_year' => '2025-2026', 'starts_on' => '2025-10-19', 'ends_on' => '2025-10-23', 'status' => 'archived']);
    $foreign = Team::create(['edition_id' => $otherEdition->id, 'name' => 'Other faction', 'code' => 'OTHER-CULT', 'status' => 'active']);
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    $before = culturalCrudSnapshot();
    foreach ([$teams[1], $foreign] as $invalid) {
        $this->post(route('tabulator.sport-results.store'), [
            'edition_sport_id' => $event->id, 'placements' => [1 => $invalid->id, 2 => $invalid->id], 'tabulator_password' => 'password',
        ])->assertSessionHasErrors(['placements' => 'Choose teams from the active intramurals edition.']);
        expect(culturalCrudSnapshot())->toBe($before);
    }
});

test('cultural events can link to a sports point system while remaining cultural and outside athlete limits', function () {
    [$admin, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $this->actingAs($admin)->put(route('admin.cultural.update', $event), culturalCrudPayload($this, $event, ['point_system' => 'sports_major']))
        ->assertSessionHasNoErrors();
    expect($event->fresh()->scoring_rules['point_system'])->toBe('sports_major')
        ->and(app(\App\Services\EligibilityService::class)->isParticipationSport($event->fresh()))->toBeFalse()
        ->and(app(\App\Services\EligibilityService::class)->requiresMedicalCertificate($event->fresh()))->toBeFalse();
    $group = $this->get(route('admin.sports-points.index'))->viewData('pointSystems')->firstWhere('key', 'sports_major');
    expect($group['configured_count'])->toBe(1)->and($group['configured_items'][0]['name'])->toBe('Festival Dance');
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $this->actingAs($tabulator)->get(route('tabulator.dashboard', ['category' => 'cultural']))->assertOk()->assertSee('Festival Dance');
});

test('changing a declared cultural event point system requires a password and preserves winners and declaration history', function () {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $event = culturalCrudEvent($edition, 'CULT-CUSTOM-PAINTING', 'New Painting', 'cultural_standard_10_7_5');
    $other = culturalCrudEvent($edition, 'CULT-QUIZ', 'Existing Quiz', 'cultural_major_15_10_7');
    $standings = app(StandingsService::class);
    $result = $standings->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $admin);
    $otherResult = $standings->recordSportResult($other, [1 => $teams[1]->id], $admin);
    $this->actingAs($admin);
    $payload = culturalCrudPayload($this, $event, ['point_system' => 'cultural_major_15_10_7']);
    $before = culturalCrudSnapshot();
    $this->put(route('admin.cultural.update', $event), [...$payload, 'admin_password' => 'incorrect'])->assertSessionHasErrors('admin_password')->assertSessionMissing('_old_input.admin_password');
    expect(culturalCrudSnapshot())->toBe($before);
    $original = $result->fresh()->getAttributes();
    $otherOriginal = $otherResult->fresh()->getAttributes();
    $this->put(route('admin.cultural.update', $event), [...$payload, 'admin_password' => 'password'])->assertSessionHasNoErrors();
    $after = $result->fresh()->getAttributes();
    expect(array_column($result->fresh()->points_json, 'points'))->toBe([15, 10, 7]);
    $after['points_json'] = $original['points_json'];
    expect($after)->toBe($original)->and($otherResult->fresh()->getAttributes())->toBe($otherOriginal)
        ->and((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(15.0);
    $public = $standings->declaredResultsFor($edition)->firstWhere('id', $event->id);
    expect($public['placements']->pluck('points')->all())->toBe([15.0, 10.0, 7.0]);
});

test('renaming a declared event keeps its exact awarded points and requires no points password', function () {
    [$admin, , $teams] = culturalCrudFixture();
    $edition = IntramuralEdition::where('status', 'active')->sole();
    $event = culturalCrudEvent($edition);
    $result = app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin);
    $before = $result->fresh()->getAttributes();
    $tallies = DB::table('team_tallies')->get()->toJson();
    $this->actingAs($admin)->put(route('admin.cultural.update', $event), culturalCrudPayload($this, $event, ['name' => 'Renamed Festival']))->assertSessionHasNoErrors();
    expect($result->fresh()->getAttributes())->toBe($before)->and(DB::table('team_tallies')->get()->toJson())->toBe($tallies);
});

test('a new point system cannot silently remove previously declared placements', function () {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $admin);
    $this->actingAs($admin);
    $payload = culturalCrudPayload($this, $event, ['point_system' => 'cultural_special_2', 'admin_password' => 'password']);
    $before = culturalCrudSnapshot();
    $this->put(route('admin.cultural.update', $event), $payload)->assertSessionHasErrors('point_system');
    expect(culturalCrudSnapshot())->toBe($before);
});

test('unscored cultural events can be archived and restored without deleting schedules or registrations', function () {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $student = Student::factory()->create(['status' => 'active']);
    $entry = AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $teams[0]->id, 'status' => 'active']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'title' => 'Cultural schedule', 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'MPCC', 'status' => 'scheduled']);
    $this->actingAs($admin);
    $version = culturalCrudPayload($this, $event)['event_version'];
    $beforeEntry = $entry->fresh()->getAttributes();
    $beforeSchedule = $schedule->fresh()->getAttributes();
    $this->delete(route('admin.cultural.destroy', $event), ['event_version' => $version])->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe('archived')->and($entry->fresh()->getAttributes())->toBe($beforeEntry)->and($schedule->fresh()->getAttributes())->toBe($beforeSchedule);
    expect(app(StandingsService::class)->scoringEventsFor($edition)->pluck('id')->all())->not->toContain($event->id);
    expect(fn () => app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin))->toThrow(ValidationException::class);
    $reference = culturalCrudEvent($edition, 'CULT-OTHER-DANCE', 'Other Dance');
    $rules = $reference->scoring_rules;
    $rules['placements'][0]['points'] = 31;
    $reference->update(['scoring_rules' => $rules]);
    $page = $this->get(route('admin.cultural.index', ['status' => 'archived']))->assertOk()->assertSee('Restore event');
    $this->post(route('admin.cultural.restore', $event), ['event_version' => $page->viewData('versions')[$event->id]])->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe('preparation')
        ->and((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(31.0)
        ->and(app(StandingsService::class)->scoringEventsFor($edition)->pluck('id')->all())->toContain($event->id);
});

test('events with declared winners cannot be archived', function () {
    [$admin, $edition, $teams] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin);
    $this->actingAs($admin);
    $version = culturalCrudPayload($this, $event)['event_version'];
    $before = culturalCrudSnapshot();
    $this->delete(route('admin.cultural.destroy', $event), ['event_version' => $version])->assertSessionHasErrors('event');
    expect(culturalCrudSnapshot())->toBe($before);
});

test('assigning points to a non-scoring cultural event uses the current group rules', function () {
    [$admin, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition, 'CULT-CAMPUS-ART', 'Campus Art', 'cultural_standard_10_7_5');
    $event->update(['scoring_rules' => [...$event->scoring_rules, 'non_scoring' => true]]);
    $reference = culturalCrudEvent($edition, 'CULT-OTHER-ART', 'Other Art', 'cultural_standard_10_7_5');
    $rules = $reference->scoring_rules;
    $rules['placements'][0]['points'] = 12;
    $reference->update(['scoring_rules' => $rules]);
    $this->actingAs($admin)->put(route('admin.cultural.update', $event), culturalCrudPayload($this, $event))->assertSessionHasNoErrors();
    expect($event->fresh()->scoring_rules['non_scoring'])->toBeFalse()
        ->and((float) $event->fresh()->scoring_rules['placements'][0]['points'])->toBe(12.0)
        ->and(app(StandingsService::class)->scoringEventsFor($edition)->pluck('id')->all())->toContain($event->id);
});

test('stale cultural forms cannot overwrite another administrators changes', function () {
    [$admin, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $this->actingAs($admin);
    $payload = culturalCrudPayload($this, $event, ['name' => 'Stale Rename']);
    $event->update(['rules' => 'New instructions from another admin']);
    $before = culturalCrudSnapshot();
    $this->put(route('admin.cultural.update', $event), $payload)->assertSessionHasErrors('event_version');
    $this->delete(route('admin.cultural.destroy', $event), ['event_version' => $payload['event_version']])->assertSessionHasErrors('event_version');
    expect(culturalCrudSnapshot())->toBe($before);
});

test('cultural CRUD rejects invalid systems duplicate names sports and archived editions without partial writes', function () {
    [$admin, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $sport = culturalCrudEvent($edition, 'CHESS-MEN', 'Chess', 'sports_minor');
    $this->actingAs($admin);
    $before = culturalCrudSnapshot();
    $this->post(route('admin.cultural.store'), ['edition_id' => $edition->id, 'name' => 'Festival Dance', 'status' => 'active', 'participant_type' => 'team', 'point_system' => 'NO-SYSTEM'])->assertSessionHasErrors(['name', 'point_system']);
    $this->get(route('admin.cultural.edit', $sport))->assertNotFound();
    $this->put(route('admin.cultural.update', $sport), [])->assertNotFound();
    expect(culturalCrudSnapshot())->toBe($before);
    $payload = culturalCrudPayload($this, $event, ['name' => 'Archived Rename']);
    $edition->update(['status' => 'archived']);
    $before = culturalCrudSnapshot();
    $this->put(route('admin.cultural.update', $event), $payload)->assertSessionHasErrors('edition_id');
    expect(culturalCrudSnapshot())->toBe($before);
});

test('only administrators can use cultural CRUD', function (string $role) {
    [, $edition] = culturalCrudFixture();
    $event = culturalCrudEvent($edition);
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);
    $before = culturalCrudSnapshot();
    $this->actingAs($user)->get(route('admin.cultural.index'))->assertForbidden();
    $this->get(route('admin.cultural.create'))->assertForbidden();
    $this->get(route('admin.cultural.edit', $event))->assertForbidden();
    $this->post(route('admin.cultural.store'), [])->assertForbidden();
    $this->put(route('admin.cultural.update', $event), [])->assertForbidden();
    $this->delete(route('admin.cultural.destroy', $event), [])->assertForbidden();
    $this->post(route('admin.cultural.restore', $event), [])->assertForbidden();
    expect(culturalCrudSnapshot())->toBe($before);
})->with(['gam', 'tabulator', 'coordinator']);
