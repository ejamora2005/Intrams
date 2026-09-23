<?php

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

function bracketEngineEdition(): IntramuralEdition
{
    return IntramuralEdition::query()->create([
        'name' => 'Bracket Engine Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
}

function bracketEngineSport(IntramuralEdition $edition, string $participantType, string $mechanic = 'single_elimination'): EditionSport
{
    $sport = Sport::query()->create([
        'name' => 'Bracket '.Str::headline($participantType).' '.Str::headline($mechanic),
        'code' => 'BRACKET-'.Str::upper(Str::random(10)),
        'status' => 'active',
    ]);

    return EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => $participantType,
        'game_mechanic' => $mechanic,
        'status' => 'active',
    ]);
}

function finishReadyBracketMatches(EditionSport $editionSport): void
{
    $attempts = 0;

    while ($match = BracketMatch::query()
        ->where('edition_sport_id', $editionSport->id)
        ->where('status', 'pending')
        ->whereNotNull('competitor_one_id')
        ->whereNotNull('competitor_two_id')
        ->orderByRaw("case bracket when 'winners' then 1 when 'losers' then 2 else 3 end")
        ->orderBy('round_number')
        ->orderBy('match_number')
        ->first()) {
        abort_if(++$attempts > 64, 500, 'The bracket did not complete.');
        app(BracketService::class)->record($match, BracketCompetitor::query()->findOrFail($match->competitor_one_id));
    }
}

test('an odd-sized double-elimination bracket advances byes and completes every path', function () {
    $edition = bracketEngineEdition();
    $editionSport = bracketEngineSport($edition, 'team', 'double_elimination');

    foreach (range(1, 5) as $number) {
        $team = Team::query()->create([
            'edition_id' => $edition->id,
            'name' => 'ODD TEAM '.$number,
            'code' => 'ODDTEAM'.$number,
            'status' => 'active',
        ]);
        $student = Student::factory()->create(['status' => 'active']);
        AthleteEntry::query()->create([
            'edition_sport_id' => $editionSport->id,
            'student_id' => $student->id,
            'team_id' => $team->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    app(BracketService::class)->initialize($editionSport);

    expect(BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->where('type', 'team')->count())->toBe(5);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->count())->toBe(15);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'bye')->count())->toBeGreaterThan(0);

    finishReadyBracketMatches($editionSport);

    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'pending')->count())->toBe(0);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('bracket', 'finals')->where('round_number', 1)->value('winner_competitor_id'))->not->toBeNull();
});

test('dual pairs become double-elimination competitors and advance as pairs', function () {
    $edition = bracketEngineEdition();
    $editionSport = bracketEngineSport($edition, 'dual', 'double_elimination');

    foreach (range(1, 3) as $pairNumber) {
        $pairKey = (string) Str::uuid();
        foreach (range(1, 2) as $studentNumber) {
            $student = Student::factory()->create([
                'status' => 'active',
                'first_name' => 'Pair'.$pairNumber,
                'last_name' => 'Student'.$studentNumber,
            ]);
            AthleteEntry::query()->create([
                'edition_sport_id' => $editionSport->id,
                'student_id' => $student->id,
                'pair_key' => $pairKey,
                'status' => 'active',
                'assigned_at' => now(),
            ]);
        }
    }

    app(BracketService::class)->initialize($editionSport);

    $competitors = BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->where('type', 'dual')->get();
    expect($competitors)->toHaveCount(3);
    expect($competitors->every(fn (BracketCompetitor $competitor) => count($competitor->athlete_entry_ids) === 2 && str_contains($competitor->label, ' / ')))->toBeTrue();

    finishReadyBracketMatches($editionSport);

    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'pending')->count())->toBe(0);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('bracket', 'finals')->where('round_number', 1)->value('winner_competitor_id'))->not->toBeNull();
});

test('individual athletes use the same bracket engine without team records', function () {
    $edition = bracketEngineEdition();
    $editionSport = bracketEngineSport($edition, 'individual');

    foreach (range(1, 3) as $number) {
        $student = Student::factory()->create([
            'status' => 'active',
            'first_name' => 'Individual',
            'last_name' => 'Athlete'.$number,
        ]);
        AthleteEntry::query()->create([
            'edition_sport_id' => $editionSport->id,
            'student_id' => $student->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    app(BracketService::class)->initialize($editionSport);

    expect(BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->where('type', 'individual')->count())->toBe(3);

    finishReadyBracketMatches($editionSport);

    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'pending')->count())->toBe(0);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('bracket', 'winners')->orderByDesc('round_number')->value('winner_competitor_id'))->not->toBeNull();
});

test('an administrator can reset only the current sport bracket without changing registrations', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = bracketEngineEdition();
    $editionSport = bracketEngineSport($edition, 'team');

    foreach (range(1, 4) as $number) {
        $team = Team::query()->create([
            'edition_id' => $edition->id,
            'name' => 'RESET TEAM '.$number,
            'code' => 'RESETTEAM'.$number,
            'status' => 'active',
        ]);
        $student = Student::factory()->create(['status' => 'active']);
        AthleteEntry::query()->create([
            'edition_sport_id' => $editionSport->id,
            'student_id' => $student->id,
            'team_id' => $team->id,
            'status' => 'active',
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.sports.bracket', [$editionSport->sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Reset bracket');

    $match = BracketMatch::query()
        ->where('edition_sport_id', $editionSport->id)
        ->where('round_number', 1)
        ->where('match_number', 1)
        ->firstOrFail();
    $this->actingAs($admin)
        ->post(route('admin.sports.bracket.result', [$editionSport->sport, $match]), [
            'edition_id' => $edition->id,
            'winner_competitor_id' => $match->competitor_one_id,
        ])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    $oldMatchIds = BracketMatch::query()->where('edition_sport_id', $editionSport->id)->pluck('id');
    $entryIds = AthleteEntry::query()->where('edition_sport_id', $editionSport->id)->pluck('id');

    $this->actingAs($admin)
        ->from(route('admin.sports.bracket', [$editionSport->sport, 'edition_id' => $edition->id]))
        ->post(route('admin.sports.bracket.reset', $editionSport->sport), ['edition_id' => $edition->id])
        ->assertSessionHasErrors('confirmation');

    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'completed')->count())->toBe(1);

    $this->actingAs($admin)
        ->post(route('admin.sports.bracket.reset', $editionSport->sport), [
            'edition_id' => $edition->id,
            'confirmation' => 'RESET',
        ])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->whereIn('id', $oldMatchIds)->count())->toBe(0);
    expect(BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'completed')->count())->toBe(0);
    expect(AthleteEntry::query()->whereIn('id', $entryIds)->count())->toBe(4);
    expect(BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->count())->toBe(4);
    $this->assertDatabaseHas('audit_logs', ['action' => 'bracket.reset', 'auditable_id' => $editionSport->id]);
});
