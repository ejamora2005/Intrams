<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Support\Str;

function manualBracketFixture(string $participantType, string $mechanic, int $count): EditionSport
{
    $edition = IntramuralEdition::create([
        'name' => 'Manual bracket test', 'school_year' => '2026-2027',
        'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active',
    ]);
    $sport = Sport::create(['name' => 'Manual bracket', 'code' => 'MANUAL-'.Str::random(10), 'status' => 'active']);
    $configuration = EditionSport::create([
        'edition_id' => $edition->id, 'sport_id' => $sport->id,
        'participant_type' => $participantType, 'game_mechanic' => $mechanic, 'status' => 'active',
    ]);

    foreach (range(1, $count) as $number) {
        $team = Team::create([
            'edition_id' => $edition->id, 'name' => 'Faction '.$number,
            'code' => 'FACTION-'.$number, 'status' => 'active',
        ]);
        $pairKey = $participantType === 'dual' ? (string) Str::uuid() : null;
        foreach (range(1, $participantType === 'dual' ? 2 : 1) as $member) {
            AthleteEntry::create([
                'edition_sport_id' => $configuration->id,
                'student_id' => Student::factory()->create(['status' => 'active'])->id,
                'team_id' => $participantType === 'individual' ? null : $team->id,
                'pair_key' => $pairKey, 'status' => 'active', 'assigned_at' => now(),
            ]);
        }
    }

    app(BracketService::class)->initialize($configuration);

    return $configuration;
}

test('manual bracket slots and JSON saves retain the chosen competitors after reload', function (string $type, string $mechanic) {
    $this->withoutMiddleware(VerifyCsrfToken::class);
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $configuration = manualBracketFixture($type, $mechanic, 3);
    $parameters = ['sport' => $configuration->sport_id, 'edition_id' => $configuration->edition_id];
    $url = route('admin.sports.bracket', $parameters);

    $view = $this->get($url)->assertOk()->assertViewHas('bracketBlank', true);
    // For an odd round-robin field the third competitor needs an actual
    // drop target in a later round, even though the opening round has two.
    $dom = new DOMDocument;
    @$dom->loadHTML($view->getContent());
    $xpath = new DOMXPath($dom);
    $indexes = [];
    foreach ($xpath->query('//*[@data-bracket-slot-card]') as $slot) {
        $indexes[] = $slot->getAttribute('data-bracket-slot-index');
    }
    expect(count(array_unique($indexes)))->toBe($mechanic === 'round_robin' ? 3 : 4);
    expect($xpath->query('//*[@data-lineup-count]')->length)->toBe(1);

    $ordered = BracketCompetitor::where('edition_sport_id', $configuration->id)->orderByDesc('id')->pluck('id')->all();
    $this->postJson(route('admin.sports.bracket.order', $parameters), ['competitor_ids' => $ordered])
        ->assertOk()->assertJsonPath('redirect_url', $url);
    expect($configuration->fresh()->bracket_lineup_configured_at)->not->toBeNull();

    $opening = BracketMatch::where('edition_sport_id', $configuration->id)
        ->where('bracket', $mechanic === 'round_robin' ? 'round_robin' : 'winners')
        ->where('round_number', 1)->orderBy('match_number')->firstOrFail();
    expect([$opening->competitor_one_id, $opening->competitor_two_id])->toBe(array_slice($ordered, 0, 2));
    $this->get($url)->assertOk()->assertViewHas('bracketBlank', false)
        ->assertViewHas('selectedCompetitors', fn ($competitors) => $competitors->pluck('id')->sort()->values()->all() === collect($ordered)->sort()->values()->all());
})->with([
    ['team', 'round_robin'], ['individual', 'round_robin'], ['dual', 'round_robin'],
    ['team', 'single_elimination'], ['team', 'double_elimination'],
]);

test('manual round robin preserves opening slots and produces every pairing once', function (int $count) {
    $configuration = manualBracketFixture('team', 'round_robin', $count);
    $ordered = BracketCompetitor::where('edition_sport_id', $configuration->id)->orderByDesc('id')->pluck('id')->all();
    app(BracketService::class)->reorder($configuration, $ordered);
    $matches = BracketMatch::where('edition_sport_id', $configuration->id)->orderBy('round_number')->orderBy('match_number')->get();
    expect($matches)->toHaveCount(intdiv($count * ($count - 1), 2));
    $pairs = $matches->map(fn ($match) => collect([$match->competitor_one_id, $match->competitor_two_id])->sort()->implode('-'));
    expect($pairs->unique())->toHaveCount($pairs->count());
    foreach ($matches->groupBy('round_number') as $round) {
        $appearances = $round->flatMap(fn ($match) => [$match->competitor_one_id, $match->competitor_two_id]);
        expect($appearances->unique())->toHaveCount($appearances->count());
    }
    expect([$matches->first()->competitor_one_id, $matches->first()->competitor_two_id])->toBe(array_slice($ordered, 0, 2));
})->with([3, 4, 5]);

test('a saved manual matchup accepts its winner and displays the recorded result', function (string $type, string $mechanic) {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $configuration = manualBracketFixture($type, $mechanic, 3);
    $ordered = BracketCompetitor::where('edition_sport_id', $configuration->id)->orderByDesc('id')->pluck('id')->all();
    app(BracketService::class)->reorder($configuration, $ordered);
    $url = route('admin.sports.bracket', ['sport' => $configuration->sport_id, 'edition_id' => $configuration->edition_id]);
    $match = BracketMatch::where('edition_sport_id', $configuration->id)->where('status', 'pending')
        ->whereNotNull('competitor_one_id')->whereNotNull('competitor_two_id')->orderBy('id')->firstOrFail();

    $this->from($url)->post(route('admin.sports.bracket.result', [$configuration->sport_id, $match]), [
        'edition_id' => $configuration->edition_id,
        'winner_competitor_id' => $match->competitor_two_id,
    ])->assertRedirect($url)->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($match->fresh()->status)->toBe('completed')
        ->and($match->fresh()->winner_competitor_id)->toBe($match->competitor_two_id)
        ->and($match->fresh()->loser_competitor_id)->toBe($match->competitor_one_id);
    $this->get($url)->assertOk()->assertSee(in_array($mechanic, ['round_robin', 'custom'], true) ? 'Winner recorded' : 'Winner advanced automatically');
})->with([
    ['team', 'round_robin'], ['individual', 'round_robin'], ['dual', 'round_robin'],
    ['team', 'single_elimination'], ['team', 'double_elimination'], ['team', 'custom'],
]);

test('stale or incomplete winner submissions return to the bracket with an explanation', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    $configuration = manualBracketFixture('team', 'round_robin', 3);
    $competitors = BracketCompetitor::where('edition_sport_id', $configuration->id)->pluck('id')->all();
    app(BracketService::class)->reorder($configuration, $competitors);
    $url = route('admin.sports.bracket', ['sport' => $configuration->sport_id, 'edition_id' => $configuration->edition_id]);
    $match = BracketMatch::where('edition_sport_id', $configuration->id)->orderBy('id')->firstOrFail();
    $resultUrl = route('admin.sports.bracket.result', [$configuration->sport_id, $match]);
    $otherCompetitor = collect($competitors)->diff([$match->competitor_one_id, $match->competitor_two_id])->first();

    $this->from($url)->post($resultUrl, ['edition_id' => $configuration->edition_id, 'winner_competitor_id' => $otherCompetitor])
        ->assertRedirect($url)->assertSessionHasErrors('winner_competitor_id');
    $this->get($url)->assertOk()->assertSee('The matchup has changed.');
    expect($match->fresh()->status)->toBe('pending');

    $this->from($url)->post($resultUrl, ['edition_id' => $configuration->edition_id])
        ->assertRedirect($url)->assertSessionHasErrors('winner_competitor_id');
    expect($match->fresh()->status)->toBe('pending');

    $this->from($url)->post($resultUrl, ['edition_id' => $configuration->edition_id, 'winner_competitor_id' => $match->competitor_one_id])
        ->assertRedirect($url)->assertSessionHasNoErrors();
    $this->from($url)->post($resultUrl, ['edition_id' => $configuration->edition_id, 'winner_competitor_id' => $match->competitor_two_id])
        ->assertRedirect($url)->assertSessionHasErrors('winner_competitor_id');
    $this->get($url)->assertOk()->assertSee('This match already has a winner.');
    expect($match->fresh()->winner_competitor_id)->toBe($match->competitor_one_id);

    $unready = BracketMatch::where('edition_sport_id', $configuration->id)->where('status', 'pending')->firstOrFail();
    $unready->update(['competitor_two_id' => null]);
    $this->from($url)->post(route('admin.sports.bracket.result', [$configuration->sport_id, $unready]), [
        'edition_id' => $configuration->edition_id, 'winner_competitor_id' => $unready->competitor_one_id,
    ])->assertRedirect($url)->assertSessionHasErrors('winner_competitor_id');
    expect($unready->fresh()->status)->toBe('pending');
});
