<?php

use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\AthleteEntry;
use App\Models\TeamMember;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\StandingsService;
use Database\Seeders\AdminUserSeeder;
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
    $team = operationsTeam(operationsEdition());
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);

    $this->actingAs($gam)->get(route('dashboard'))->assertRedirect(route('gam.dashboard'));
    $this->actingAs($gam)->get(route('gam.dashboard'))
        ->assertOk()
        ->assertSee('GAM workspace')
        ->assertSee('INTRAMURALS 2026: STUDENT FESTIVAL')
        ->assertSee('Compete. Create. Lead. Connect. Express.')
        ->assertSee('Roster')
        ->assertSee('Medical Certificates')
        ->assertSee('Rules & Guidelines')
        ->assertSee('Teams and rosters')
        ->assertSee('data-ops-view-panel="dashboard-overview"', false)
        ->assertSee('data-ops-view-panel="roster-management"', false)
        ->assertSee('data-ops-view-target="medical-certificates"', false)
        ->assertSee('Mighty Sea Dragons');
});

test('GAM users can add players to their assigned faction', function () {
    $team = operationsTeam(operationsEdition());
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);

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
    $team = operationsTeam(operationsEdition());

    $this->actingAs($admin)->post(route('admin.operations-accounts.store'), [
        'name' => 'Tabulator Two',
        'email' => 'tabulator-two@example.com',
        'role' => 'tabulator',
        'status' => 'active',
        'managed_team_id' => $team->id,
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.operations-accounts.store'), [
        'name' => 'GAM Two',
        'email' => 'gam-two@example.com',
        'role' => 'gam',
        'status' => 'active',
        'managed_team_id' => $team->id,
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'tabulator-two@example.com', 'role' => 'tabulator', 'managed_team_id' => $team->id]);
    $this->assertDatabaseHas('users', ['email' => 'gam-two@example.com', 'role' => 'gam', 'managed_team_id' => $team->id]);
});

test('default operations accounts seed with all-faction access', function () {
    $previous = $_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] ?? null;
    putenv('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD=password');
    $_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = 'password';
    $_SERVER['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = 'password';

    try {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'gam@example.com', 'role' => 'gam', 'managed_team_id' => null]);
        $this->assertDatabaseHas('users', ['email' => 'tabulator@example.com', 'role' => 'tabulator', 'managed_team_id' => null]);

        $this->post('/login', ['email' => 'gam@example.com', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    } finally {
        auth()->logout();

        if ($previous === null) {
            putenv('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD');
            unset($_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'], $_SERVER['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD']);
        } else {
            putenv('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD='.$previous);
            $_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = $previous;
            $_SERVER['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = $previous;
        }
    }
});

test('multiple tabulators can access the tabulator dashboard', function () {
    operationsSport(operationsEdition());

    $firstTabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $secondTabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);

    $this->actingAs($firstTabulator)->get(route('tabulator.dashboard'))
        ->assertOk()
        ->assertSee('Tabulator workspace')
        ->assertSee('INTRAMURALS 2026: STUDENT FESTIVAL')
        ->assertSee('Compete. Create. Lead. Connect. Express.')
        ->assertSee('Possible DQ')
        ->assertSee('Declare Sports')
        ->assertSee('Rules & Guidelines')
        ->assertSee('data-ops-view-panel="dashboard-overview"', false)
        ->assertSee('data-ops-view-panel="declared-results"', false)
        ->assertSee('data-ops-view-target="match-winners"', false)
        ->assertSee('Tabulator dashboard');
    $this->actingAs($secondTabulator)->get(route('tabulator.dashboard'))->assertOk()->assertSee('Tabulator dashboard');
});

test('participation rules flag possible dq students across operations dashboards', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $edition = operationsEdition();
    $team = operationsTeam($edition);
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $basketball = operationsSport($edition, 'Basketball 5x5', 'BASKET-5X5');
    $volleyball = operationsSport($edition, 'Volleyball', 'VOLLEYBALL');

    $student = Student::query()->create([
        'student_number' => 'DQ-001',
        'first_name' => 'Riley',
        'last_name' => 'Cruz',
        'school_year' => $edition->school_year,
        'course_id' => $team->course_id,
        'status' => 'active',
    ]);

    TeamMember::query()->create([
        'edition_id' => $edition->id,
        'team_id' => $team->id,
        'student_id' => $student->id,
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    AthleteEntry::query()->create([
        'edition_sport_id' => $basketball->id,
        'student_id' => $student->id,
        'team_id' => $team->id,
        'status' => 'active',
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.sports.participants.store', $volleyball->sport), [
        'edition_id' => $edition->id,
        'team_id' => $team->id,
        'student_ids' => [$student->id],
    ])->assertSessionHasErrors('student_ids');

    AthleteEntry::query()->create([
        'edition_sport_id' => $volleyball->id,
        'student_id' => $student->id,
        'team_id' => $team->id,
        'status' => 'active',
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('admin.rules.index'))->assertOk()->assertSee('Possible DQ')->assertSee('Riley Cruz');
    $this->actingAs($gam)->get(route('gam.dashboard', ['team_id' => $team->id]))->assertOk()->assertSee('Possible DQ')->assertSee('Riley Cruz');
    $this->actingAs($tabulator)->get(route('tabulator.dashboard'))->assertOk()->assertSee('Possible DQ')->assertSee('Riley Cruz');
});

test('GAM users can verify required medical certificates but exempt sports stay hidden', function () {
    $edition = operationsEdition();
    $team = operationsTeam($edition);
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $volleyball = operationsSport($edition, 'Volleyball', 'VOLLEYBALL');
    $chess = operationsSport($edition, 'Chess', 'CHESS');
    $student = Student::query()->create([
        'student_number' => 'MED-001',
        'first_name' => 'Mika',
        'last_name' => 'Santos',
        'school_year' => $edition->school_year,
        'course_id' => $team->course_id,
        'status' => 'active',
    ]);

    $physicalEntry = AthleteEntry::query()->create([
        'edition_sport_id' => $volleyball->id,
        'student_id' => $student->id,
        'team_id' => $team->id,
        'status' => 'active',
        'medical_certificate_status' => 'pending',
        'assigned_at' => now(),
    ]);

    AthleteEntry::query()->create([
        'edition_sport_id' => $chess->id,
        'student_id' => $student->id,
        'team_id' => $team->id,
        'status' => 'active',
        'medical_certificate_status' => 'not_required',
        'assigned_at' => now(),
    ]);

    $this->actingAs($gam)->get(route('gam.dashboard'))
        ->assertOk()
        ->assertSee('Medical certificate verification')
        ->assertSee('Volleyball')
        ->assertDontSee('>Chess</td>', false);

    $this->actingAs($gam)->post(route('gam.medical-certificates.update', $physicalEntry), [
        'medical_certificate_status' => 'verified',
        'medical_certificate_notes' => 'Cleared',
    ])->assertRedirect();

    $this->assertDatabaseHas('athlete_entries', [
        'id' => $physicalEntry->id,
        'medical_certificate_status' => 'verified',
        'medical_certificate_reviewed_by' => $gam->id,
        'medical_certificate_notes' => 'Cleared',
    ]);
});

test('unassigned GAM users can manage all active factions', function () {
    $edition = operationsEdition();
    $firstTeam = operationsTeam($edition, 'Mighty Sea Dragons', 'mighty-sea-dragons', 'Marine Biology', 'MB');
    $secondTeam = operationsTeam($edition, 'Trojan Warriors', 'trojan-warriors', 'Information Technology', 'IT');
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => null]);

    $this->actingAs($gam)->get(route('gam.dashboard'))
        ->assertOk()
        ->assertSee('This GAM account can manage all active factions.')
        ->assertSee('Mighty Sea Dragons')
        ->assertSee('Trojan Warriors');

    $this->actingAs($gam)->post(route('gam.teams.players.store', $secondTeam), [
        'student_number' => 'GLOBAL-GAM-001',
        'first_name' => 'Global',
        'last_name' => 'Player',
        'course_id' => $secondTeam->course_id,
    ])->assertRedirect(route('gam.dashboard', ['team_id' => $secondTeam->id]));

    $this->assertDatabaseHas('team_members', [
        'team_id' => $secondTeam->id,
        'student_id' => Student::query()->where('student_number', 'GLOBAL-GAM-001')->value('id'),
    ]);

    expect($firstTeam->fresh()->members()->count())->toBe(0);
});

test('GAM users cannot manage another faction', function () {
    $edition = operationsEdition();
    $assignedTeam = operationsTeam($edition, 'Mighty Sea Dragons', 'mighty-sea-dragons', 'Marine Biology', 'MB');
    $otherTeam = operationsTeam($edition, 'Terraquatic Eagles', 'terraquatic-eagles', 'Education', 'EDU');
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $assignedTeam->id]);

    $this->actingAs($gam)->get(route('gam.dashboard'))
        ->assertOk()
        ->assertSee('Mighty Sea Dragons')
        ->assertDontSee('Terraquatic Eagles');

    $this->actingAs($gam)->post(route('gam.teams.players.store', $otherTeam), [
        'student_number' => 'BLOCKED-001',
        'first_name' => 'Blocked',
        'last_name' => 'Player',
    ])->assertNotFound();

    $this->assertDatabaseMissing('students', ['student_number' => 'BLOCKED-001']);
});
