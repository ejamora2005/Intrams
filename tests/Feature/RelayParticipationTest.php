<?php

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\EligibilityService;
use Illuminate\Support\Facades\DB;

function relayRuleFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Open relay rules', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Relay faction', 'code' => 'RELAY-TEAM', 'status' => 'active']);
    $student = Student::factory()->create(['gender' => 'Female', 'school_year' => $edition->school_year, 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);

    return [$edition, $team, $student];
}

function relayRuleEvent(IntramuralEdition $edition, string $code, string $system = 'sports_athletics', ?string $name = null): EditionSport
{
    $sport = Sport::create(['code' => $code, 'name' => $name ?? $code, 'participant_category' => 'male', 'status' => 'active']);

    return EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'scoring_rules' => ['point_system' => $system], 'status' => 'active']);
}

function relayRuleEntry(Student $student, Team $team, EditionSport $event): AthleteEntry
{
    return AthleteEntry::create(['student_id' => $student->id, 'team_id' => $team->id, 'edition_sport_id' => $event->id, 'status' => 'active']);
}

test('Admin and GAM can register any gender for gender-labelled relays without a separate run', function (string $role) {
    [$edition, $team, $student] = relayRuleFixture();
    $user = User::factory()->create(['role' => $role, 'status' => 'active', 'managed_team_id' => $role === 'gam' ? $team->id : null]);
    $event = relayRuleEvent($edition, 'RELAY-4X100-MEN', 'sports_major', '4x100m Relay (Men)');
    expect($event->sport->participant_category)->toBe('open')->and($event->sport->name)->toBe('4x100m Relay (Men)');
    $url = $role === 'admin' ? route('admin.students.update', $student) : route('gam.teams.players.sports.update', [$team, $student]);
    $payload = ['edition_sport_ids' => [$event->id]];
    if ($role === 'admin') {
        $payload += [...$student->only(['first_name', 'middle_name', 'last_name', 'course_id', 'gender', 'school_year', 'status']), 'year_level' => '1st', 'section' => 'A'];
    }
    $this->actingAs($user)->put($url, $payload)->assertSessionHasNoErrors()->assertSessionMissing('participation_warning')->assertRedirect();
    $evaluation = app(EligibilityService::class)->evaluateStudent($student, $edition);
    expect($evaluation['counts'])->toBe(['major' => 0, 'minor' => 0, 'individual_or_dual' => 1, 'other' => 0])
        ->and($evaluation['possible_dq'])->toBeFalse();
    $this->get(route($role.'.possible-dq.index'))->assertOk()->assertDontSee($student->student_number);
})->with(['admin', 'gam']);

test('duplicate relays count once per distance as individual regardless of scoring group', function () {
    [$edition, $team, $student] = relayRuleFixture();
    foreach ([['RELAY-4X100-MEN', 'sports_major'], ['RELAY-4X100-WOMEN', 'sports_minor'], ['RELAY-4X400-MEN', 'sports_athletics']] as [$code, $system]) {
        relayRuleEntry($student, $team, relayRuleEvent($edition, $code, $system));
    }
    $service = app(EligibilityService::class);
    $evaluation = $service->evaluateStudent($student, $edition);
    expect($evaluation['total'])->toBe(2)->and($evaluation['entries'])->toHaveCount(3)
        ->and($evaluation['counts'])->toBe(['major' => 0, 'minor' => 0, 'individual_or_dual' => 2, 'other' => 0])
        ->and($evaluation['possible_dq'])->toBeFalse();
    expect(collect($evaluation['entries'])->pluck('slot_label')->unique()->all())->toBe(['Relay (Individual/Dual)']);

    // Normal event limits still flag an actual invalid combination.
    $major = relayRuleEvent($edition, 'VOLLEYBALL-WOMEN', 'sports_major', 'Volleyball (Women)');
    $major->sport->update(['participant_category' => 'female']);
    relayRuleEntry($student, $team, $major);
    expect($service->evaluateStudent($student, $edition)['possible_dq'])->toBeTrue();
});

test('qualifying runs exempt every relay and withdrawing the run restores individual counts', function () {
    [$edition, $team, $student] = relayRuleFixture();
    $relay = relayRuleEvent($edition, 'RELAY-4X100-WOMEN', 'sports_minor');
    relayRuleEntry($student, $team, $relay);
    relayRuleEntry($student, $team, relayRuleEvent($edition, 'RELAY-4X400-MEN', 'sports_major'));
    $run = relayRuleEvent($edition, '100M-RUN-WOMEN', 'sports_athletics', '100m Run (Women)');
    $service = app(EligibilityService::class);
    expect($service->countsTowardParticipationLimit($relay, $student))->toBeTrue();
    // Preview must use the proposed run before it has an entry.
    $preview = $service->evaluateStudent($student, $edition, $run);
    expect($preview['total'])->toBe(1)->and($preview['possible_dq'])->toBeFalse();
    $entry = relayRuleEntry($student, $team, $run);
    $evaluation = $service->evaluateStudent($student, $edition);
    expect($evaluation['total'])->toBe(1)->and($evaluation['counts']['individual_or_dual'])->toBe(1)
        ->and($evaluation['possible_dq'])->toBeFalse()->and($service->countsTowardParticipationLimit($relay, $student))->toBeFalse();
    expect(collect($evaluation['entries'])->where('is_relay', true)->pluck('slot_label')->unique()->all())->toBe(['Relay']);
    $entry->update(['status' => 'inactive']);
    expect($service->evaluateStudent($student, $edition)['total'])->toBe(2)
        ->and($service->evaluateStudent($student, $edition)['possible_dq'])->toBeFalse()
        ->and($service->countsTowardParticipationLimit($relay, $student))->toBeTrue();
});

test('another edition or an inactive qualifying run cannot exempt current relays', function () {
    [$edition, $team, $student] = relayRuleFixture();
    relayRuleEntry($student, $team, relayRuleEvent($edition, 'RELAY-4X100-WOMEN'));
    $otherEdition = IntramuralEdition::create(['name' => 'Old edition', 'school_year' => '2025-2026', 'starts_on' => '2025-10-19', 'ends_on' => '2025-10-23', 'status' => 'archived']);
    relayRuleEntry($student, $team, relayRuleEvent($otherEdition, '200M-RUN-WOMEN', 'sports_athletics', '200m Run (Women)'));
    $inactive = relayRuleEntry($student, $team, relayRuleEvent($edition, '400M-RUN-WOMEN', 'sports_athletics', '400m Run (Women)'));
    $inactive->update(['status' => 'inactive']);
    expect(app(EligibilityService::class)->evaluateStudent($student, $edition)['counts']['individual_or_dual'])->toBe(1);
});

test('relay migration opens existing records without changing registrations schedules results points or statuses', function () {
    [$edition, $team, $student] = relayRuleFixture();
    $event = relayRuleEvent($edition, 'RELAY-4X100-MEN', 'sports_athletics', '4x100m Relay (Men)');
    relayRuleEntry($student, $team, $event);
    CompetitionSchedule::create(['edition_sport_id' => $event->id, 'title' => 'Final relay', 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'Field/Oval', 'status' => 'completed']);
    $user = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    SportResult::create(['edition_sport_id' => $event->id, 'submitted_by' => $user->id, 'placements_json' => [['team_id' => $team->id, 'placement' => 1]], 'points_json' => [['team_id' => $team->id, 'points' => 10]], 'submitted_at' => now()]);
    DB::table('sports')->where('id', $event->sport_id)->update(['name' => '4x100m Relay (Men)', 'participant_category' => 'male', 'status' => 'inactive']);
    $tables = ['edition_sports', 'athlete_entries', 'competition_schedules', 'schedule_participants', 'sport_results', 'team_tallies', 'bracket_matches', 'bracket_competitors'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    $before = $snapshot();
    $migration = require database_path('migrations/2026_10_20_040000_make_relay_events_open.php');
    $migration->up();
    expect($snapshot())->toBe($before)->and($event->sport->fresh()->name)->toBe('4x100m Relay (Men)')
        ->and($event->sport->fresh()->participant_category)->toBe('open')->and($event->sport->fresh()->status)->toBe('inactive');
    $auditCount = AuditLog::count();
    $migration->up();
    expect(AuditLog::count())->toBe($auditCount)->and($snapshot())->toBe($before);
});

test('relay edits preserve unique gendered names while accepting every gender', function () {
    [$edition] = relayRuleFixture();
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $events = collect(['MEN' => 'Men', 'WOMEN' => 'Women'])->map(fn ($label, $suffix) => relayRuleEvent($edition, 'RELAY-4X100-'.$suffix, 'sports_athletics', '4x100m Relay ('.$label.')'));
    foreach ($events as $label => $event) {
        $page = $this->actingAs($admin)->get(route('admin.sports.edit', ['sport' => $event->sport, 'edition_id' => $edition->id]))
            ->assertOk()->assertSee('Relay events accept all genders.')->assertSee('4x100m Relay ('.ucfirst(strtolower($label)).')');
        $beforeName = $event->sport->getRawOriginal('name');
        $this->put(route('admin.sports.update', $event->sport), ['edition_id' => $edition->id, 'event_version' => $page->viewData('version'), 'point_system' => 'sports_athletics', 'name' => $event->sport->name, 'status' => 'active', 'participant_category' => 'male', 'participant_type' => 'team', 'game_mechanic' => 'custom', 'configuration_status' => 'active', 'rules' => null])
            ->assertSessionHasNoErrors()->assertRedirect();
        expect($event->sport->fresh()->getRawOriginal('name'))->toBe($beforeName)
            ->and($event->sport->fresh()->participant_category)->toBe('open')
            ->and($event->sport->fresh()->acceptsStudentGender('Female'))->toBeTrue()
            ->and($event->sport->fresh()->acceptsStudentGender('Male'))->toBeTrue();
    }
});
