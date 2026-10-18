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
