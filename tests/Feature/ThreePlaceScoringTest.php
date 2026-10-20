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
use App\Services\StandingsService;
use Carbon\Carbon;
use Database\Seeders\DefaultSportsSeeder;
use Illuminate\Support\Facades\DB;

function threePlaceFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Three-place scoring', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $user = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Trojan Warriors', 'Terraquatic Eagles', 'Historical Fourth'])->map(fn ($name, $index) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'THREE-'.$index, 'status' => 'active']));
    $sport = Sport::create(['name' => '100m Run (Men)', 'code' => '100M-RUN-MEN', 'participant_category' => 'male', 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'status' => 'active', 'scoring_rules' => ['point_system' => 'sports_athletics', 'placements' => config('intramurals.point_systems.sports_athletics.placements')]]);

    return [$edition, $user, $teams, $event];
}

function legacyFourPlaceRules(): array
{
    return ['point_system' => 'sports_athletics', 'placements' => collect([5, 3, 2, 1])->map(fn ($points, $index) => ['placement' => $index + 1, 'label' => ($index + 1).' Place', 'points' => $points, 'medal' => ['gold', 'silver', 'bronze', null][$index]])->all()];
}

test('all default athletics use champion and two runners up worth ten seven and five points', function () {
    $this->seed(DefaultSportsSeeder::class);
    $events = EditionSport::with('sport')->get()->filter(fn ($event) => $event->sport->isTrackAndField());
    expect($events)->not->toBeEmpty();
    foreach ($events as $event) {
        $rules = app(StandingsService::class)->pointRulesFor($event);
        expect(array_column($rules, 'label'))->toBe(['Champion', '1st Runner-up', '2nd Runner-up'])
            ->and(array_column($rules, 'points'))->toBe([10.0, 7.0, 5.0]);
    }
    expect(config('intramurals.point_systems.sports_major.placements.0.points'))->toBe(25.0)
        ->and(config('intramurals.point_systems.sports_minor.placements.0.points'))->toBe(20.0);
});

test('migration rescores all current four place systems while preserving winners and declaration history', function () {
    [$edition, $user, $teams, $event] = threePlaceFixture();
    $event->update(['scoring_rules' => legacyFourPlaceRules()]);
    $cultureSport = Sport::create(['name' => 'Cultural four place', 'code' => 'CULT-FOUR-PLACE', 'status' => 'active']);
    $culture = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $cultureSport->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active', 'scoring_rules' => ['placements' => legacyFourPlaceRules()['placements'], 'custom_note' => 'Keep this rule metadata']]);
    $majorSport = Sport::create(['name' => 'Basketball', 'code' => 'BASKET-5X5-MEN', 'status' => 'active']);
    $majorRules = ['point_system' => 'sports_major', 'placements' => config('intramurals.point_systems.sports_major.placements')];
    $major = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $majorSport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => $majorRules]);
    $archived = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => Sport::create(['name' => 'Archived', 'code' => 'ARCHIVED-FOUR', 'status' => 'active'])->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'archived', 'scoring_rules' => legacyFourPlaceRules()]);
    $majorBefore = $major->fresh()->getAttributes();
    $archivedBefore = $archived->fresh()->getAttributes();
    $placements = $teams->mapWithKeys(fn ($team, $i) => [$i + 1 => $team->id])->all();
    $standings = app(StandingsService::class);
    foreach ([$event, $culture, $major] as $item) {
        $standings->recordSportResult($item, $item->is($major) ? array_slice($placements, 0, 3, true) : $placements, $user);
    }
    $before = SportResult::all()->mapWithKeys(fn ($result) => [$result->id => $result->getAttributes()]);
    BracketMatch::create(['edition_sport_id' => $major->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'team_one_id' => $teams[0]->id, 'winner_team_id' => $teams[0]->id, 'status' => 'completed']);
    CompetitionSchedule::create(['edition_sport_id' => $event->id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 09:00:00', 'status' => 'scheduled']);
    $matches = DB::table('bracket_matches')->get()->toJson();
    $schedules = DB::table('competition_schedules')->get()->toJson();
    $migration = require database_path('migrations/2026_10_20_040000_apply_three_place_athletics_scoring.php');
    $migration->up();
    foreach ([$event, $culture] as $item) {
        expect($standings->pointRulesFor($item->fresh()))->toHaveCount(3);
        expect(array_column($standings->pointRulesFor($item->fresh()), 'points'))->toBe([10.0, 7.0, 5.0]);
        $result = $item->sportResult()->firstOrFail();
        $attributes = $result->getAttributes();
        $attributes['points_json'] = $before[$result->id]['points_json'];
        expect($attributes)->toBe($before[$result->id])->and($result->placements_json)->toBe($placements);
    }
    expect($major->fresh()->getAttributes())->toBe($majorBefore)
        ->and($culture->fresh()->scoring_rules['custom_note'])->toBe('Keep this rule metadata')
        ->and($archived->fresh()->getAttributes())->toBe($archivedBefore)
        ->and(DB::table('bracket_matches')->get()->toJson())->toBe($matches)
        ->and(DB::table('competition_schedules')->get()->toJson())->toBe($schedules);
    foreach ([45, 34, 25, 0] as $index => $points) {
        expect((float) TeamTally::where('team_id', $teams[$index]->id)->value('points'))->toBe((float) $points);
    }
    $this->actingAs($user)->get(route('tabulator.dashboard', ['view' => 'declared-results']))->assertOk()
        ->assertDontSee('<span class="tab-rank-small">#4</span>', false);
    $audits = AuditLog::count();
    $tallies = DB::table('team_tallies')->get()->toJson();
    $migration->up();
    expect(AuditLog::count())->toBe($audits)->and(DB::table('team_tallies')->get()->toJson())->toBe($tallies);
});

test('three place athletics still allows several placed athletes from one faction and rejects fourth place', function () {
    [$edition, $user, $teams, $event] = threePlaceFixture();
    $this->actingAs($user);
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id, 2 => $teams[0]->id, 3 => $teams[0]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    expect((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(22.0);
    $this->get(route('tabulator.dashboard', ['edition_sport_id' => $event->id]))->assertOk()
        ->assertSee('data-allow-repeated-winners="true"', false)->assertDontSee('name="placements[4]"', false)
        ->assertSee('Champion')->assertSee('1st Runner-up')->assertSee('2nd Runner-up');
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [4 => $teams[1]->id], 'tabulator_password' => 'password'])->assertSessionHasErrors('placements');
    expect((float) TeamTally::where('team_id', $teams[0]->id)->value('points'))->toBe(22.0);
});

test('declarations store UTC and display precise Philippine time across the calendar day boundary', function () {
    [$edition, $user, $teams, $event] = threePlaceFixture();
    $this->travelTo(Carbon::parse('2026-10-20 23:15:42', 'UTC'));
    $this->actingAs($user)->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id], 'tabulator_password' => 'password'])->assertSessionHasNoErrors();
    $result = SportResult::where('edition_sport_id', $event->id)->firstOrFail();
    expect(DB::table('sport_results')->where('id', $result->id)->value('submitted_at'))->toBe('2026-10-20 23:15:42');
    foreach ([[], ['edition_sport_id' => $event->id]] as $parameters) {
        $this->get(route('tabulator.dashboard', $parameters))->assertOk()
            ->assertSee('Oct 21, 2026 7:15:42 AM')->assertSee('PHT (UTC+8)')
            ->assertSee('datetime="2026-10-21T07:15:42+08:00"', false);
    }
    expect($result->fresh()->submitted_at->format('Y-m-d H:i:s'))->toBe('2026-10-20 23:15:42');
    $this->travelBack();
});
