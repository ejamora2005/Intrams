<?php

use App\Models\AuditLog;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function journalScenario(): array
{
    $password = Hash::make('journal-test-password');
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => $password]);
    $tabulator = User::factory()->create(['name' => 'Official Tabulator', 'role' => 'tabulator', 'status' => 'active', 'password' => $password]);
    $edition = IntramuralEdition::create(['name' => 'Journal edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $teams = collect(['Sea Dragons', 'Trojan Warriors', 'Terraquatic Eagles'])->map(fn ($name) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => Str::slug($name), 'status' => 'active']));
    $sport = Sport::create(['name' => 'Basketball 5x5 (Men)', 'code' => 'BASKET-5X5-MEN', 'status' => 'active']);
    $event = $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => ['point_system' => 'sports_major', 'placements' => [
        ['placement' => 1, 'label' => 'Champion', 'points' => 20, 'medal' => 'gold'],
        ['placement' => 2, 'label' => 'First runner-up', 'points' => 15, 'medal' => 'silver'],
        ['placement' => 3, 'label' => 'Second runner-up', 'points' => 10, 'medal' => 'bronze'],
    ]]]);

    return [$admin, $tabulator, $event, $teams];
}

function journalRequest(string $action, array $values, array $attributes = []): AuditLog
{
    return AuditLog::create(['action' => 'request.'.$action, 'auditable_type' => \Illuminate\Http\Request::class, 'outcome' => 'success', 'request_id' => (string) Str::uuid(), 'created_at' => now(), 'new_values' => $values, ...$attributes]);
}

test('the activity register excludes page visits searches authentication downloads and unchanged saves without deleting history', function () {
    [$admin] = journalScenario();
    app(AuditService::class)->record('student.created', newValues: ['first_name' => 'Formal', 'last_name' => 'Player'], actor: $admin);
    $read = journalRequest('admin.students.index', ['request' => ['method' => 'GET', 'input' => ['search' => 'Private search phrase']], 'changes' => []]);
    journalRequest('admin.sports.score-sheet.download', ['request' => ['method' => 'POST'], 'changes' => [['type' => 'User', 'id' => $admin->id, 'after' => ['name' => 'Download noise']]]]);
    journalRequest('admin.students.update', ['request' => ['method' => 'PUT'], 'changes' => []]);
    AuditLog::create(['action' => 'auth.login', 'auditable_type' => User::class, 'outcome' => 'success', 'created_at' => now()]);
    $count = AuditLog::count();
    $response = $this->actingAs($admin)->get(route('admin.system-logs.index'))->assertOk();
    expect($response->viewData('logs')->total())->toBe(1)->and((int) $response->viewData('summary')->total)->toBe(1);
    $response->assertDontSee('Private search phrase')->assertDontSee('Download noise')->assertDontSee('auth.login')->assertDontSee('<pre', false);
    $this->get(route('admin.system-logs.index', ['request_id' => $read->request_id]))->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
    expect(AuditLog::count())->toBe($count + 2)->and(AuditLog::find($read->id))->not->toBeNull();
});

test('one course save produces one formal entry even when it has multiple technical audit records', function () {
    [$admin] = journalScenario();
    $this->actingAs($admin)->post(route('admin.courses.store'), ['name' => 'Formal Course'])->assertRedirect();
    $request = AuditLog::where('action', 'request.admin.courses.store')->firstOrFail();
    expect(AuditLog::where('request_id', $request->request_id)->count())->toBeGreaterThan(1)
        ->and($request->new_values['activity']['subject'])->toBe('Formal Course');
    $response = $this->get(route('admin.system-logs.index', ['request_id' => $request->request_id]))->assertOk()->assertSee('Course created')->assertSee('Formal Course');
    expect($response->viewData('logs')->total())->toBe(1);
    $response->assertDontSee('course.created')->assertDontSee('request.admin.courses.store')->assertDontSee('HTTP 302')->assertDontSee('<pre', false);
    $this->get(route('admin.system-logs.index', ['activity' => 'create']))->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
});

test('tabulator declarations show the event ranked factions and awarded points with historical names retained', function () {
    [$admin, $tabulator, $event, $teams] = journalScenario();
    $this->actingAs($tabulator)->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id, 2 => $teams[1]->id, 3 => $teams[2]->id], 'tabulator_password' => 'journal-test-password'])->assertRedirect();
    $log = AuditLog::where('action', 'request.tabulator.sport-results.store')->firstOrFail();
    expect($log->new_values['activity']['title'])->toBe('Event winners declared')
        ->and($log->new_values['activity']['details'])->toContain('Champion: Sea Dragons · 20 points', 'First runner-up: Trojan Warriors · 15 points');
    $event->sport->update(['name' => 'Renamed later']);
    $teams[0]->update(['name' => 'Renamed faction']);
    $tabulator->update(['name' => 'Renamed staff']);
    $response = $this->actingAs($admin)->get(route('admin.system-logs.index', ['activity' => 'results']))->assertOk();
    expect($response->viewData('logs')->total())->toBe(1);
    preg_match('/<article data-activity-record=.*?<\/article>/s', $response->getContent(), $article);
    expect($article[0])->toContain('Official Tabulator', 'Tabulator', 'Basketball 5x5 (Men)', 'Sea Dragons', 'Champion', '20 points')
        ->not->toContain('Renamed later', 'Renamed faction', 'Renamed staff', 'journal-test-password', 'placements_json');
});

test('match winner declarations identify the selected winner and suppress the duplicate bracket domain entry', function () {
    [$admin, $tabulator, $event, $teams] = journalScenario();
    $competitors = $teams->take(2)->map(fn ($team) => BracketCompetitor::create(['edition_sport_id' => $event->id, 'type' => 'team', 'team_id' => $team->id, 'label' => $team->name, 'identity_key' => 'team:'.$team->id]));
    $match = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitors[0]->id, 'competitor_two_id' => $competitors[1]->id, 'team_one_id' => $teams[0]->id, 'team_two_id' => $teams[1]->id, 'status' => 'pending']);
    $this->actingAs($tabulator)->post(route('tabulator.matches.result', $match), ['winner_competitor_id' => $competitors[1]->id, 'tabulator_password' => 'journal-test-password'])->assertRedirect();
    expect($match->fresh()->winner_competitor_id)->toBe($competitors[1]->id);
    $request = AuditLog::where('action', 'request.tabulator.matches.result')->firstOrFail();
    expect($request->new_values['activity']['title'])->toBe('Match winner declared')
        ->and($request->new_values['activity']['details'])->toContain('Winner: Trojan Warriors');
    $this->actingAs($admin)->get(route('admin.system-logs.index', ['request_id' => $request->request_id]))->assertOk()->assertSee('Match winner declared')->assertSee('Winner: Trojan Warriors')->assertSee('Round 1')->assertDontSee('bracket.match.completed')
        ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
});

test('legacy declarations resolve event and faction names and remain searchable by sport', function () {
    [$admin, $tabulator, $event, $teams] = journalScenario();
    journalRequest('tabulator.sport-results.store', ['request' => ['method' => 'POST', 'input' => ['edition_sport_id' => $event->id, 'placements' => [1 => $teams[0]->id]]], 'changes' => [['type' => 'SportResult', 'event' => 'created', 'after' => ['edition_sport_id' => $event->id, 'placements_json' => [1 => $teams[0]->id]]]]], ['actor_role' => 'tabulator', 'user_id' => $tabulator->id]);
    $event->sport->delete();
    $teams[0]->delete();
    $this->actingAs($admin)->get(route('admin.system-logs.index', ['search' => 'Basketball']))->assertOk()->assertSee('Basketball 5x5 (Men)')->assertSee('Champion: Sea Dragons')->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
});

test('unsuccessful changes are clearly described as attempted and do not expose raw or sensitive data', function () {
    [$admin] = journalScenario();
    $student = Student::factory()->create(['status' => 'active']);
    journalRequest('admin.students.update', ['request' => ['method' => 'PUT', 'parameters' => ['student' => ['type' => 'Student', 'id' => $student->id, 'label' => $student->full_name]], 'input' => ['password' => 'legacy-private-password']], 'changes' => [['type' => 'Student', 'id' => $student->id, 'before' => ['status' => 'active', 'password' => 'secret-old'], 'after' => ['status' => 'inactive', 'password' => 'secret-new']]]], ['user_id' => $admin->id, 'actor_role' => 'admin', 'outcome' => 'failed']);
    $response = $this->actingAs($admin)->get(route('admin.system-logs.index', ['outcome' => 'failed']))->assertOk();
    $response->assertSee('Not completed')->assertSee('attempted action')->assertSee('Before')->assertSee('Active')->assertSee('Inactive')->assertDontSee('legacy-private-password')->assertDontSee('secret-old')->assertDontSee('secret-new')->assertDontSee('<pre', false);
    expect($student->fresh()->status)->toBe('active')->and((int) $response->viewData('summary')->issues)->toBe(1);
});

test('points recalculation is described as a configuration update rather than a new winner declaration', function () {
    [$admin, , $event, $teams] = journalScenario();
    $result = SportResult::create(['edition_sport_id' => $event->id, 'placements_json' => [1 => $teams[0]->id], 'points_json' => [], 'submitted_at' => now()]);
    app(AuditService::class)->record('sport_result.points_recalculated', $result, ['points_json' => []], ['points_json' => [1 => ['points' => 20]]], actor: $admin);
    $this->actingAs($admin)->get(route('admin.system-logs.index'))->assertOk()->assertSee('Points configuration updated')->assertSee('Basketball 5x5 (Men)')->assertDontSee('Event winners declared');
});
