<?php

use App\Models\AuditLog;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\AthleteEntryService;
use App\Services\BasketballThreeByThreeScoringService;
use App\Services\EligibilityService;
use App\Services\StandingsService;
use Database\Seeders\DefaultSportsSeeder;
use Illuminate\Support\Facades\DB;

function basketballPointsFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Basketball point correction', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $index) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'BASKET-POINTS-'.$index, 'status' => 'active']));
    $events = collect(['BASKET-3X3-MEN', 'BASKET-3X3-WOMEN', 'CHESS-MEN'])->mapWithKeys(function ($code) use ($edition): array {
        $definition = collect(config('intramurals.default_competitions'))->firstWhere('code', $code);
        $sport = Sport::create(['code' => $code, 'name' => $definition['name'], 'participant_category' => $definition['participant_category'], 'status' => 'active']);
        $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => ['point_system' => 'sports_minor', 'placements' => config('intramurals.point_systems.sports_minor.placements')]]);

        return [$code => $event];
    });
    $standings = app(StandingsService::class);
    $standings->recordSportResult($events['BASKET-3X3-MEN'], [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $user);
    $standings->recordSportResult($events['BASKET-3X3-WOMEN'], [1 => $teams[2]->id, 2 => $teams[0]->id, 3 => $teams[1]->id], $user);
    $standings->recordSportResult($events['CHESS-MEN'], [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $user);

    return [$edition, $user, $teams, $events];
}

test('basketball three by three defaults use fifteen ten seven while remaining minor participation events', function () {
    $this->seed(DefaultSportsSeeder::class);
    $edition = app(StandingsService::class)->activeEdition();
    $options = app(AthleteEntryService::class)->selectableSportsFor($edition);
    foreach (['BASKET-3X3-MEN', 'BASKET-3X3-WOMEN'] as $code) {
        $event = $edition->editionSports()->whereHas('sport', fn ($query) => $query->where('code', $code))->firstOrFail();
        expect($event->scoring_rules['point_system'])->toBe('sports_basketball_3x3')
            ->and(array_column(app(StandingsService::class)->pointRulesFor($event), 'points'))->toBe([15.0, 10.0, 7.0])
            ->and(app(EligibilityService::class)->slotFor($event))->toBe('minor')
            ->and($options->contains('id', $event->id))->toBeTrue();
    }
    expect(config('intramurals.point_systems.sports_minor.placements.0.points'))->toBe(20.0);
});

test('basketball migration corrects existing declared points and keeps winners timestamps and other sports intact', function () {
    [$edition, $user, $teams, $events] = basketballPointsFixture();
    $before = SportResult::all()->mapWithKeys(fn ($result) => [$result->id => $result->getAttributes()]);
    $chessBefore = $events['CHESS-MEN']->fresh()->getAttributes();
    BracketMatch::create(['edition_sport_id' => $events['BASKET-3X3-MEN']->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'team_one_id' => $teams[0]->id, 'winner_team_id' => $teams[0]->id, 'status' => 'completed']);
    CompetitionSchedule::create(['edition_sport_id' => $events['BASKET-3X3-MEN']->id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 09:00:00', 'status' => 'scheduled']);
    $matches = DB::table('bracket_matches')->get()->toJson();
    $schedules = DB::table('competition_schedules')->get()->toJson();
    $migration = require database_path('migrations/2026_10_20_050000_correct_basketball_3x3_points.php');
    $migration->up();
    foreach ($events as $code => $event) {
        $result = $event->sportResult()->firstOrFail();
        $attributes = $result->getAttributes();
        if ($code !== 'CHESS-MEN') {
            expect(array_column($result->points_json, 'points'))->toBe([15, 10, 7]);
            $attributes['points_json'] = $before[$result->id]['points_json'];
        }
        expect($attributes)->toBe($before[$result->id]);
    }
    expect($events['CHESS-MEN']->fresh()->getAttributes())->toBe($chessBefore)
        ->and(DB::table('bracket_matches')->get()->toJson())->toBe($matches)
        ->and(DB::table('competition_schedules')->get()->toJson())->toBe($schedules);
    foreach ([45, 32, 32] as $index => $points) {
        expect((float) TeamTally::where('team_id', $teams[$index]->id)->value('points'))->toBe((float) $points);
    }
    $auditCount = AuditLog::count();
    $tallies = DB::table('team_tallies')->get()->toJson();
    $migration->up();
    expect(AuditLog::count())->toBe($auditCount)->and(DB::table('team_tallies')->get()->toJson())->toBe($tallies);
});

test('future admin point edits update declared point details and standings without redeclaring winners', function () {
    [$edition, $user, $teams, $events] = basketballPointsFixture();
    app(BasketballThreeByThreeScoringService::class)->sync();
    $original = SportResult::all()->mapWithKeys(fn ($result) => [$result->id => $result->getAttributes()]);
    $this->actingAs($user)->put(route('admin.sports-points.update', 'sports_basketball_3x3'), [
        'systems' => ['sports_basketball_3x3' => ['admin_password' => 'password', 'placements' => [
            1 => ['label' => 'Champion', 'points' => 18, 'medal' => 'gold'],
            2 => ['label' => '1st Runner-up', 'points' => 12, 'medal' => 'silver'],
            3 => ['label' => '2nd Runner-up', 'points' => 8, 'medal' => 'bronze'],
        ]]],
    ])->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('success');
    foreach ($events as $code => $event) {
        $result = $event->sportResult()->firstOrFail();
        $attributes = $result->getAttributes();
        if ($code !== 'CHESS-MEN') {
            expect(array_column($result->points_json, 'points'))->toBe([18, 12, 8]);
            $attributes['points_json'] = $original[$result->id]['points_json'];
        }
        expect($attributes)->toBe($original[$result->id]);
    }
    foreach ([50, 35, 36] as $index => $points) {
        expect((float) TeamTally::where('team_id', $teams[$index]->id)->value('points'))->toBe((float) $points);
    }
    $public = app(StandingsService::class)->declaredResultsFor($edition)->firstWhere('id', $events['BASKET-3X3-MEN']->id);
    expect($public['placements']->pluck('points')->all())->toBe([18.0, 12.0, 8.0]);
    app(BasketballThreeByThreeScoringService::class)->sync();
    expect(array_column($events['BASKET-3X3-MEN']->fresh()->scoring_rules['placements'], 'points'))->toBe([18, 12, 8]);
});

test('changing the minor point group no longer changes basketball three by three', function () {
    [$edition, $user, $teams, $events] = basketballPointsFixture();
    app(BasketballThreeByThreeScoringService::class)->sync();
    $this->actingAs($user)->put(route('admin.sports-points.update', 'sports_minor'), ['systems' => ['sports_minor' => [
        'admin_password' => 'password', 'placements' => [1 => ['label' => 'Champion', 'points' => 21, 'medal' => 'gold']],
    ]]])->assertSessionHasNoErrors()->assertRedirect();
    expect((float) $events['CHESS-MEN']->fresh()->scoring_rules['placements'][0]['points'])->toBe(21.0)
        ->and((float) $events['BASKET-3X3-MEN']->fresh()->scoring_rules['placements'][0]['points'])->toBe(15.0)
        ->and((float) $events['BASKET-3X3-WOMEN']->fresh()->scoring_rules['placements'][0]['points'])->toBe(15.0);
    $page = $this->get(route('admin.sports-points.index'))->assertOk()->assertSee('Basketball 3x3 (Men &amp; Women)', false);
    $group = $page->viewData('pointSystems')->firstWhere('key', 'sports_basketball_3x3');
    expect($group['configured_count'])->toBe(2)->and($group['declared_count'])->toBe(2);
});
