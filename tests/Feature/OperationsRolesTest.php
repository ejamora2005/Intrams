<?php

use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Support\Facades\Hash;

function operationsEdition(): IntramuralEdition
{
    $defaultEdition = config('intramurals.default_edition');

    return IntramuralEdition::query()->create([
        'name' => $defaultEdition['name'],
        'school_year' => $defaultEdition['school_year'],
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-06',
        'status' => 'active',
    ]);
}

function operationsTeam(
    IntramuralEdition $edition,
    string $name = 'Mighty Sea Dragons',
    string $code = 'mighty-sea-dragons',
    string $courseName = 'Marine Biology',
    string $courseCode = 'MB',
): Team
{
    $course = Course::query()->create(['name' => $courseName, 'code' => $courseCode, 'status' => 'active']);

    return Team::query()->create([
        'edition_id' => $edition->id,
        'course_id' => $course->id,
        'name' => $name,
        'code' => $code,
        'status' => 'active',
    ]);
}

function operationsSport(
    IntramuralEdition $edition,
    string $name = 'Volleyball',
    string $code = 'VOLLEYBALL',
): EditionSport
{
    $sport = Sport::query()->create([
        'name' => $name,
        'code' => $code,
        'status' => 'active',
        'is_system' => true,
    ]);

    return $edition->editionSports()->create([
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'scoring_rules' => StandingsService::defaultScoringRules(),
        'status' => 'preparation',
    ]);
}

test('GAM users are sent to the GAM dashboard', function () {
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active']);
    operationsTeam(operationsEdition());

    $this->actingAs($gam)->get(route('dashboard'))->assertRedirect(route('gam.dashboard'));
    $this->actingAs($gam)->get(route('gam.dashboard'))->assertOk()->assertSee('Teams and rosters')->assertSee('Mighty Sea Dragons');
});

test('GAM users can add players to any active team', function () {
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active']);
    $team = operationsTeam(operationsEdition());

    $this->actingAs($gam)->post(route('gam.teams.players.store', $team), [
        'student_number' => 'GAM-001',
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'course_id' => $team->course_id,
        'gender' => 'Female',
        'year_level' => '2',
        'section' => 'A',
    ])->assertRedirect(route('gam.dashboard', ['team_id' => $team->id]));

    $student = Student::query()->where('student_number', 'GAM-001')->first();

    expect($student)->not->toBeNull();
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'student_id' => $student->id,
    ]);
});

test('tabulators declare sport winners and standings use admin point rules', function () {
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $edition = operationsEdition();
    $editionSport = operationsSport($edition);
    $champion = operationsTeam($edition, 'Mighty Sea Dragons', 'mighty-sea-dragons', 'Marine Biology', 'MB');
    $runnerUp = operationsTeam($edition, 'Terraquatic Eagles', 'terraquatic-eagles', 'Education', 'EDU');
    $third = operationsTeam($edition, 'Trojan Warriors', 'trojan-warriors', 'Engineering', 'ENG');

    $this->actingAs($tabulator)->post(route('tabulator.sport-results.store'), [
        'edition_sport_id' => $editionSport->id,
        'placements' => [
            1 => $champion->id,
            2 => $runnerUp->id,
            3 => $third->id,
        ],
        'tabulator_password' => 'password',
    ])->assertRedirect();

    $this->assertDatabaseHas('sport_results', ['edition_sport_id' => $editionSport->id]);

    expect((float) TeamTally::query()->where('team_id', $champion->id)->value('points'))->toBe(10.0);
    expect((float) TeamTally::query()->where('team_id', $runnerUp->id)->value('points'))->toBe(7.0);
    expect((float) TeamTally::query()->where('team_id', $third->id)->value('points'))->toBe(5.0);

    $this->get('/')->assertOk()->assertSee('10')->assertSee('Rank 01');
});

test('tabulator sport changes require the tabulator password', function () {
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $edition = operationsEdition();
    $editionSport = operationsSport($edition);
    $team = operationsTeam($edition);

    $this->actingAs($tabulator)->post(route('tabulator.sport-results.store'), [
        'edition_sport_id' => $editionSport->id,
        'placements' => [1 => $team->id],
        'tabulator_password' => 'wrong-password',
    ])->assertSessionHasErrors('tabulator_password');

    $this->assertDatabaseMissing('sport_results', ['edition_sport_id' => $editionSport->id]);
    $this->assertDatabaseMissing('team_tallies', ['team_id' => $team->id, 'points' => 10]);
});

test('admin sport point changes require the admin password', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'password' => Hash::make('correct-password'),
    ]);
    $edition = operationsEdition();
    $volleyball = operationsSport($edition);
    $basketball = operationsSport($edition, 'Basketball 5x5', 'BASKET-5X5');
    $cultural = operationsSport($edition, 'Festival Dance', 'CULT-FEST-DANCE');
    $payload = [
        'systems' => [
            'sports_major' => [
                'placements' => [
                    1 => ['label' => 'Champion', 'points' => 12, 'medal' => 'gold'],
                    2 => ['label' => 'Runner-up', 'points' => 8, 'medal' => 'silver'],
                    3 => ['label' => 'Third place', 'points' => 4, 'medal' => 'bronze'],
                ],
            ],
        ],
    ];

    $this->actingAs($admin)->put(route('admin.sports-points.update', 'sports_major'), [
        ...$payload,
        'systems' => [
            'sports_major' => [
                ...$payload['systems']['sports_major'],
                'admin_password' => 'wrong-password',
            ],
        ],
    ])->assertSessionHasErrors('systems.sports_major.admin_password');

    expect((float) $volleyball->fresh()->scoring_rules['placements'][0]['points'])->toBe(10.0);

    $this->actingAs($admin)->put(route('admin.sports-points.update', 'sports_major'), [
        ...$payload,
        'systems' => [
            'sports_major' => [
                ...$payload['systems']['sports_major'],
                'admin_password' => 'correct-password',
            ],
        ],
    ])->assertRedirect();

    expect((float) $volleyball->fresh()->scoring_rules['placements'][0]['points'])->toBe(12.0)
        ->and((float) $basketball->fresh()->scoring_rules['placements'][0]['points'])->toBe(12.0)
        ->and((float) $cultural->fresh()->scoring_rules['placements'][0]['points'])->toBe(10.0);
});

test('admins can create multiple tabulator and GAM accounts', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

    $this->actingAs($admin)->post(route('admin.operations-accounts.store'), [
        'name' => 'Tabulator Two',
        'email' => 'tabulator-two@example.com',
        'role' => 'tabulator',
        'status' => 'active',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.operations-accounts.store'), [
        'name' => 'GAM Two',
        'email' => 'gam-two@example.com',
        'role' => 'gam',
        'status' => 'active',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'tabulator-two@example.com', 'role' => 'tabulator']);
    $this->assertDatabaseHas('users', ['email' => 'gam-two@example.com', 'role' => 'gam']);
});

test('multiple tabulators can access the tabulator dashboard', function () {
    operationsSport(operationsEdition());

    $firstTabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $secondTabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);

    $this->actingAs($firstTabulator)->get(route('tabulator.dashboard'))->assertOk()->assertSee('Tabulator dashboard');
    $this->actingAs($secondTabulator)->get(route('tabulator.dashboard'))->assertOk()->assertSee('Tabulator dashboard');
});
