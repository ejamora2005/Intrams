<?php

use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\StandingsService;
use Database\Seeders\DefaultSportsSeeder;

function tabulatorScoringFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Scoring edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $events = collect(['BASKET-5X5-MEN' => 'Basketball 5x5 (Men)', 'CULT-PAINTING' => 'Painting', '100M-RUN-MEN' => '100m Run (Men)'])->map(function ($name, $code) use ($edition) {
        $sport = Sport::create(['name' => $name, 'code' => $code, 'status' => 'active']);

        return EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'scoring_rules' => StandingsService::defaultScoringRules(), 'status' => 'active']);
    })->values();
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $i) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'SCORE-'.$i, 'status' => 'active']));

    return [$edition, $events, $teams];
}

test('tabulator workspace and guidance pages handle an empty database', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    $this->get(route('tabulator.dashboard'))->assertOk()->assertSee('No scoring events configured')->assertSee('No ready matches to score');
    $this->get(route('tabulator.rules.index'))->assertOk()->assertSee('Scoring checklist');
    $this->get(route('tabulator.possible-dq.index'))->assertOk()->assertSee('No Possible DQ students found');
});

test('tabulator cards show the scoring catalogue without preselecting an event', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events] = tabulatorScoringFixture();
    $this->get(route('tabulator.dashboard'))->assertOk()->assertViewHas('selectedSport', null)
        ->assertViewHas('pendingCount', 3)->assertViewHas('declaredCount', 0)
        ->assertSee('data-tabulator-workspace', false)->assertSee('data-category="cultural"', false)
        ->assertSee('data-category="track-field"', false)->assertSee('Score events')->assertSee('Results &amp; standings', false);
    $this->get(route('tabulator.dashboard', ['edition_sport_id' => $events[1]->id]))->assertOk()
        ->assertViewHas('selectedSport', fn ($selected) => $selected->id === $events[1]->id)
        ->assertSee('data-tab-score-form', false)->assertSee('Review placements');
});

test('password errors return to the selected event and retain placements without flashing passwords', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[0]->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'incorrect-secret'])
        ->assertRedirect(route('tabulator.dashboard', ['edition_sport_id' => $events[0]->id, 'view' => 'declare-sports', 'modal' => 'tabulator-declare-sports-modal']).'#declare-sports')
        ->assertSessionHasErrors('tabulator_password')->assertSessionHas('_old_input.placements.1', $teams[0]->id)
        ->assertSessionMissing('_old_input.tabulator_password');
    expect(SportResult::count())->toBe(0);
});

test('unconfigured placements and non scoring events cannot change standings', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[0]->id, 'placements' => [99 => $teams[0]->id], 'tabulator_password' => 'password'])->assertSessionHasErrors('placements');
    $events[1]->update(['scoring_rules' => ['non_scoring' => true]]);
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[1]->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'password'])->assertSessionHasErrors('placements');
    expect(SportResult::count())->toBe(0)->and(TeamTally::count())->toBe(0);
});

test('only saved bracket lineups are offered and invalid match results keep the scoring context', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $competitors = $teams->map(fn ($team) => BracketCompetitor::create(['edition_sport_id' => $events[0]->id, 'type' => 'team', 'identity_key' => 'team:'.$team->id, 'team_id' => $team->id, 'label' => $team->name]));
    $match = BracketMatch::create(['edition_sport_id' => $events[0]->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitors[0]->id, 'competitor_two_id' => $competitors[1]->id, 'status' => 'pending']);
    $this->get(route('tabulator.dashboard'))->assertViewHas('matches', fn ($matches) => $matches->isEmpty());
    $events[0]->update(['bracket_lineup_configured_at' => now()]);
    $this->get(route('tabulator.dashboard'))->assertOk()->assertViewHas('matches', fn ($matches) => $matches->count() === 1)->assertSee('data-tab-match-form', false);
    $this->post(route('tabulator.matches.result', $match), ['match_id' => $match->id, 'winner_competitor_id' => $competitors[2]->id, 'tabulator_password' => 'password'])
        ->assertRedirect(route('tabulator.dashboard', ['view' => 'match-winners', 'modal' => 'match-winner-modal-'.$match->id]).'#match-winners')->assertSessionHasErrors('winner_competitor_id');
    expect($match->fresh()->status)->toBe('pending');
});

test('editing placements replaces the event totals instead of adding duplicate points', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[0]->id, 'placements' => [1 => $teams[0]->id, 2 => $teams[1]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[0]->id, 'placements' => [1 => $teams[1]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    expect(SportResult::count())->toBe(1)->and((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(0.0)
        ->and((float) TeamTally::where('team_id', $teams[1]->id)->value('points'))->toBe(10.0);
});

test('tabulators declare repeated factions and correct every configured cultural event and bonus award with its own points', function () {
    $user = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $this->actingAs($user);
    app(DefaultSportsSeeder::class)->run();
    $edition = app(StandingsService::class)->activeEdition();
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $i) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'CULT-SCORE-'.$i, 'status' => 'active']));
    $cultural = $edition->editionSports()->with('sport')->whereHas('sport', fn ($query) => $query->where('code', 'like', 'CULT-%'))->get()
        ->filter(fn ($event) => ! ($event->scoring_rules['non_scoring'] ?? false));
    $expectedCodes = collect(config('intramurals.default_competitions'))->filter(fn ($sport) => str_starts_with($sport['code'], 'CULT-') && ! ($sport['scoring_rules']['non_scoring'] ?? false))->pluck('code')->sort()->values()->all();
    $this->get(route('tabulator.dashboard', ['category' => 'cultural']))->assertOk()->assertSee('Score cultural events')
        ->assertSee('<option value="cultural" selected>', false)
        ->assertViewHas('editionSports', fn ($events) => $events->filter(fn ($event) => str_starts_with($event->sport->code, 'CULT-'))->pluck('sport.code')->sort()->values()->all() === $expectedCodes);

    $expectedTotals = [0.0, 0.0, 0.0];
    foreach ($cultural as $event) {
        expect(app(StandingsService::class)->allowsRepeatedWinnersFor($event))->toBeTrue();
        $placements = collect($event->scoring_rules['placements'])->mapWithKeys(fn ($rule) => [$rule['placement'] => $teams[0]->id])->all();
        $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => $placements, 'tabulator_password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('tabulator.dashboard', ['edition_sport_id' => $event->id]).'#declare-sports');
        $result = SportResult::where('edition_sport_id', $event->id)->firstOrFail();
        expect($result->placements_json)->toBe($placements)->and($result->submitted_by)->toBe($user->id);
        foreach ($event->scoring_rules['placements'] as $rule) {
            $expectedTotals[0] += $rule['points'];
            expect((float) $result->points_json[$rule['placement']]['points'])->toBe((float) $rule['points']);
        }
    }
    foreach ($teams as $i => $team) {
        expect((float) TeamTally::where('team_id', $team->id)->value('points'))->toBe($expectedTotals[$i]);
    }

    $event = $cultural->firstWhere('sport.code', 'CULT-FEST-DANCE');
    $this->get(route('tabulator.dashboard', ['edition_sport_id' => $event->id]))->assertOk()->assertSee('Cultural event')->assertSee('Editing declared results')
        ->assertViewHas('allowRepeatedWinners', true)->assertSee('data-allow-repeated-winners="true"', false)
        ->assertSee('This event allows a faction to receive more than one placement.');
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[2]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    $expectedTotals[0] -= 25 + 20 + 15;
    $expectedTotals[2] += 25;
    foreach ($teams as $i => $team) {
        expect((float) TeamTally::where('team_id', $team->id)->value('points'))->toBe($expectedTotals[$i]);
    }
    expect(SportResult::count())->toBe($cultural->count());
});

test('all default gender divisions replace generic and explicit open entries in tabulator cards and counts', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    app(DefaultSportsSeeder::class)->run();
    $edition = app(StandingsService::class)->activeEdition();
    $expectedIds = app(StandingsService::class)->scoringEventsFor($edition)->pluck('id')->sort()->values()->all();
    $legacyEvents = collect(config('intramurals.default_competitions'))
        ->filter(fn ($sport) => in_array($sport['participant_category'] ?? null, ['male', 'female'], true))
        ->map(fn ($sport) => preg_replace('/-(MEN|WOMEN)$/', '', $sport['code']))->unique()
        ->flatMap(function ($base) use ($edition) {
            return collect(['' => 'active', '-OPEN' => 'active'])->map(function ($status, $suffix) use ($edition, $base) {
                $sport = Sport::create(['name' => 'Legacy '.$base.$suffix, 'code' => $base.$suffix, 'participant_category' => 'open', 'status' => $status]);

                return EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'preparation']);
            })->values();
        })->values();
    foreach (['BADMINTON', 'TABLE-TENNIS'] as $code) {
        $sport = Sport::create(['name' => 'Legacy '.$code, 'code' => $code, 'participant_category' => 'open', 'status' => 'active']);
        EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    }

    $this->get(route('tabulator.dashboard', ['edition_sport_id' => $legacyEvents[0]->id]))->assertOk()
        ->assertViewHas('editionSports', fn ($events) => $events->pluck('id')->sort()->values()->all() === $expectedIds)
        ->assertViewHas('pendingCount', count($expectedIds))->assertViewHas('selectedSport', null)
        ->assertSee('Basketball 5x5 (Men)')->assertSee('Basketball 5x5 (Women)')
        ->assertSee('Mixed Volleyball (Open)')->assertDontSee('Legacy BASKET-5X5');
});

test('archived inactive and deleted events cannot return through tabulator scoring or match winners', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $legacy = Sport::create(['name' => 'Basketball 5x5', 'code' => 'BASKET-5X5', 'status' => 'active', 'participant_category' => 'open']);
    $openEvent = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $legacy->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'bracket_lineup_configured_at' => now()]);
    $competitors = $teams->take(2)->map(fn ($team) => BracketCompetitor::create(['edition_sport_id' => $openEvent->id, 'type' => 'team', 'identity_key' => 'team:'.$team->id, 'team_id' => $team->id, 'label' => $team->name]));
    $match = BracketMatch::create(['edition_sport_id' => $openEvent->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitors[0]->id, 'competitor_two_id' => $competitors[1]->id, 'status' => 'pending']);
    $events[1]->update(['status' => 'archived']);
    $events[2]->sport->update(['status' => 'inactive']);

    $this->get(route('tabulator.dashboard'))->assertOk()
        ->assertViewHas('editionSports', fn ($current) => $current->pluck('id')->all() === [$events[0]->id])
        ->assertViewHas('matches', fn ($matches) => $matches->isEmpty());
    foreach ([$openEvent, $events[1], $events[2]] as $event) {
        $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'password'])
            ->assertSessionHasErrors('edition_sport_id');
    }
    $events[0]->update(['status' => 'archived']);
    $this->get(route('tabulator.dashboard'))->assertOk()->assertViewHas('editionSports', fn ($current) => $current->isEmpty());
    $events[0]->update(['status' => 'active']);
    $events[0]->sport->update(['status' => 'archived']);
    $openEvent->update(['status' => 'archived']);
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $events[0]->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'password'])
        ->assertSessionHasErrors('edition_sport_id');
    $this->post(route('tabulator.matches.result', $match), ['winner_competitor_id' => $competitors[0]->id, 'tabulator_password' => 'password'])
        ->assertSessionHasErrors('edition_sport_id');
    $openEvent->update(['status' => 'archived']);
    $events[0]->sport->delete();
    $this->get(route('tabulator.dashboard'))->assertOk()->assertViewHas('editionSports', fn ($current) => $current->isEmpty());
    expect(SportResult::count())->toBe(0)->and(TeamTally::count())->toBe(0)->and($match->fresh()->status)->toBe('pending');
});

test('open replacement is edition specific preserves formats and retains historical results', function () {
    $this->actingAs(User::factory()->create(['role' => 'tabulator', 'status' => 'active']));
    [$edition, $events, $teams] = tabulatorScoringFixture();
    $otherEdition = IntramuralEdition::create(['name' => 'Other edition', 'school_year' => '2025-2026', 'starts_on' => '2025-10-19', 'ends_on' => '2025-10-23', 'status' => 'archived']);
    $newEvents = [];
    foreach ([
        ['Basketball 3x3', 'BASKET-3X3', 'open'],
        ['Mixed Volleyball (Open)', 'MIXED-VOLLEYBALL', 'mixed'],
        ['Local Game', 'LOCAL-GAME', 'open'],
        ['Basketball 5x5 (Open)', 'CUSTOM-OLD', 'open'],
    ] as [$name, $code, $category]) {
        $sport = Sport::create(['name' => $name, 'code' => $code, 'participant_category' => $category, 'status' => 'active']);
        $newEvents[$code] = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    }
    $otherSport = Sport::create(['name' => 'Local Game (Men)', 'code' => 'LOCAL-GAME-MEN', 'participant_category' => 'male', 'status' => 'active']);
    EditionSport::create(['edition_id' => $otherEdition->id, 'sport_id' => $otherSport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    app(StandingsService::class)->recordSportResult($newEvents['CUSTOM-OLD'], [1 => $teams[0]->id], auth()->user());

    $this->get(route('tabulator.dashboard'))->assertOk()
        ->assertViewHas('editionSports', fn ($current) => $current->count() === 6 && ! $current->contains('id', $newEvents['CUSTOM-OLD']->id))
        ->assertViewHas('declaredCount', 0)->assertViewHas('recentResults', fn ($results) => $results->isEmpty());
    expect(SportResult::where('edition_sport_id', $newEvents['CUSTOM-OLD']->id)->exists())->toBeTrue()
        ->and((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(10.0);
});
