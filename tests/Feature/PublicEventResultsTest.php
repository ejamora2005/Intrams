<?php

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\StandingsService;
use Carbon\Carbon;

function publicResultsFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Public results edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $user = User::factory()->create(['role' => 'tabulator', 'status' => 'active', 'email' => 'private-score-account@example.test']);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'])->map(fn ($name, $i) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => ['sea-dragons', 'terraquatic-eagles', 'trojan-warriors'][$i], 'status' => 'active']));

    return [$edition, $user, $teams];
}

function publicResultsEvent(IntramuralEdition $edition, string $name, string $code, array $rules = []): EditionSport
{
    $sport = Sport::create(['name' => $name, 'code' => $code, 'status' => 'active']);

    return EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active', 'scoring_rules' => $rules ?: StandingsService::defaultScoringRules()]);
}

test('guests can read every declared event across sports cultural and athletics with factions points and Philippine times', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $standings = app(StandingsService::class);
    foreach (range(0, 14) as $index) {
        [$name, $code] = match ($index) {
            0 => ['Volleyball (Men)', 'VOLLEYBALL-MEN'],
            1 => ['Cultural: Quiz Challenge', 'CULT-QUIZ'],
            2 => ['100m Run (Women)', '100M-RUN-WOMEN'],
            default => ['Declared Event '.$index, 'PUBLIC-DECLARED-'.$index],
        };
        $event = publicResultsEvent($edition, $name, $code);
        $this->travelTo(Carbon::parse('2026-10-20 23:15:42', 'UTC')->addMinutes($index));
        $standings->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], $user);
    }
    $this->travelBack();
    $response = $this->get('/')->assertOk()->assertSee('Declared event results')->assertSee('15 declared events')
        ->assertSee('Volleyball (Men)')->assertSee('Quiz Challenge')->assertSee('100m Run (Women)')
        ->assertSee('Declared Event 14')->assertSee('PHT (UTC+8)')->assertSee('Oct 21, 2026 7:15:42 AM')
        ->assertSee('10.00')->assertSee('7.00')->assertSee('5.00')->assertSee('Sea Dragons')
        ->assertSee('images/logo/sea_dragons.png', false)->assertDontSee($user->email);
    $results = $response->viewData('declaredEventResults');
    expect($results)->toHaveCount(15)->and($results->first()['name'])->toBe('Declared Event 14')
        ->and($results->last()['name'])->toBe('Volleyball (Men)')
        ->and($results->pluck('category')->unique()->sort()->values()->all())->toBe(['cultural', 'sports', 'track-field']);
    $this->assertGuest();
    $this->get('/login')->assertOk()->assertSee(url('/').'#event-results', false)->assertSee('Event results');
});

test('landing results expose separate sports and cultural faction leaders', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $standings = app(StandingsService::class);
    $sports = publicResultsEvent($edition, 'Basketball 5x5 (Men)', 'BASKET-5X5-MEN');
    $cultural = publicResultsEvent($edition, 'Cultural: Poster Contest', 'CULT-POSTER');

    $standings->recordSportResult($sports, [1 => $teams[1]->id], $user);
    $standings->recordSportResult($cultural, [1 => $teams[2]->id], $user);

    $data = $standings->landingData();
    expect($data['categoryStandings']->get('sports')->first()['team']->name)->toBe('Terraquatic Eagles')
        ->and($data['categoryStandings']->get('sports'))->toHaveCount(3)
        ->and($data['categoryStandings']->get('cultural')->first()['team']->name)->toBe('Trojan Warriors')
        ->and($data['categoryStandings']->get('cultural'))->toHaveCount(3);

    $this->get('/')->assertOk()
        ->assertSee('Sports standings')
        ->assertSee('Cultural standings')
        ->assertSee('Sea Dragons')
        ->assertSee('Terraquatic Eagles')
        ->assertSee('Trojan Warriors')
        ->assertSee('Sports results')
        ->assertSee('Cultural results');
});

test('public results exclude undeclared archived superseded and other edition events', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $standings = app(StandingsService::class);
    $current = publicResultsEvent($edition, 'Chess (Men)', 'CHESS-MEN');
    $current->sport->update(['participant_category' => 'male']);
    $open = publicResultsEvent($edition, 'Superseded Chess', 'CHESS');
    $pending = publicResultsEvent($edition, 'Pending event', 'PENDING-PUBLIC');
    $archived = publicResultsEvent($edition, 'Archived event', 'ARCHIVED-PUBLIC');
    $deleted = publicResultsEvent($edition, 'Deleted event', 'DELETED-PUBLIC');
    $nonScoring = publicResultsEvent($edition, 'School activity', 'ACTIVITY-PUBLIC', ['non_scoring' => true]);
    foreach ([$current, $open, $archived, $deleted, $nonScoring] as $event) {
        SportResult::create(['edition_sport_id' => $event->id, 'submitted_by' => $user->id, 'placements_json' => [1 => $teams[0]->id], 'submitted_at' => now()]);
    }
    $archived->update(['status' => 'archived']);
    $deleted->sport->delete();
    $past = IntramuralEdition::create(['name' => 'Previous edition', 'school_year' => '2025-2026', 'starts_on' => '2025-10-19', 'ends_on' => '2025-10-23', 'status' => 'archived']);
    $oldEvent = publicResultsEvent($past, 'Past edition event', 'OLD-PUBLIC');
    SportResult::create(['edition_sport_id' => $oldEvent->id, 'submitted_by' => $user->id, 'placements_json' => [1 => $teams[0]->id], 'submitted_at' => now()]);
    $results = $standings->declaredResultsFor($edition);
    expect($results)->toHaveCount(1)->and($results->first()['name'])->toBe('Chess (Men)');
    $this->get('/')->assertOk()->assertSee('Chess (Men)')->assertDontSee('Pending event')->assertDontSee('Past edition event')
        ->assertDontSee('Archived event')->assertDontSee('Superseded Chess')->assertDontSee('Deleted event')->assertDontSee('School activity');
});

test('updated declarations replace public placements and retired fourth places are not published', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $event = publicResultsEvent($edition, '100m Run (Men)', '100M-RUN-MEN', [
        'point_system' => 'historical_four_place_test',
        'placements' => collect([10, 7, 5, 0])->map(fn ($points, $index) => [
            'placement' => $index + 1,
            'label' => ($index + 1).' Place',
            'points' => $points,
            'medal' => ['gold', 'silver', 'bronze', null][$index],
        ])->all(),
    ]);
    $standings = app(StandingsService::class);
    $result = $standings->recordSportResult($event, [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id, 4 => $teams[0]->id], $user);
    expect($standings->declaredResultsFor($edition)->first()['placements'])->toHaveCount(3);
    $standings->recordSportResult($event, [1 => $teams[2]->id], $user);
    $placements = $this->get('/')->assertOk()->viewData('declaredEventResults')->first()['placements'];
    expect($placements)->toHaveCount(1)->and($placements->first()['team_name'])->toBe('Trojan Warriors')
        ->and($placements->first()['points'])->toBe(10.0)->and(SportResult::count())->toBe(1);
});

test('public special awards and archived factions render without exposing account details', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $event = publicResultsEvent($edition, 'Cultural Bonus: Costume', 'CULT-BONUS-COSTUME', ['placements' => [['placement' => 1, 'label' => 'Awardee', 'points' => 3, 'medal' => 'none']]]);
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $user);
    $teams[0]->delete();
    $this->get('/')->assertOk()->assertSee('Costume')->assertSee('Awardee')->assertSee('3.00')->assertSee('Sea Dragons')
        ->assertDontSee($user->email)->assertDontSee('Faction unavailable');
});

test('public results have clear empty and unavailable states', function () {
    $this->get('/')->assertOk()->assertSee('No declared results yet');
    $this->mock(StandingsService::class)->shouldReceive('landingData')->once()->andThrow(new RuntimeException('Database unavailable'));
    $this->get('/')->assertOk()->assertSee('Results are temporarily unavailable')->assertDontSee('No declared results yet');
});

test('live standings last updated displays the tally timestamp in Philippine time without changing stored times', function () {
    [$edition, $user, $teams] = publicResultsFixture();
    $event = publicResultsEvent($edition, 'Volleyball (Men)', 'VOLLEYBALL-MEN');
    $this->travelTo(Carbon::parse('2026-10-20 23:15:42', 'UTC'));
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $user);
    $before = TeamTally::where('edition_id', $edition->id)->get()->toJson();
    $response = $this->get('/')->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $timestamp = (new DOMXPath($document))->query('//p[contains(@class, "update-status")]/time')->item(0);
    expect($timestamp)->not->toBeNull()
        ->and($timestamp->textContent)->toContain('Oct 21, 2026 7:15:42 AM', 'PHT (UTC+8)')
        ->and($timestamp->getAttribute('datetime'))->toBe('2026-10-21T07:15:42+08:00')
        ->and(TeamTally::where('edition_id', $edition->id)->get()->toJson())->toBe($before);
    $this->travelBack();
});
