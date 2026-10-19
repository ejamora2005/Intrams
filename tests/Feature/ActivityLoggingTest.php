<?php

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StandingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

function activityFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Activity edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Activity faction', 'code' => 'ACTIVITY', 'status' => 'active']);
    $sport = Sport::create(['name' => 'Basketball 5x5 (Men)', 'code' => 'BASKET-5X5-MEN', 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => StandingsService::defaultScoringRules()]);
    $student = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);

    return [$edition, $team, $event, $student];
}

test('page visits and searches are recorded for every staff workspace with distinct request ids', function (string $role, string $route) {
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);
    $this->actingAs($user)->get(route($route, ['search' => 'test player']))->assertOk();
    $first = AuditLog::where('action', 'request.'.$route)->firstOrFail();
    expect($first->user_id)->toBe($user->id)->and($first->actor_role)->toBe($role)
        ->and($first->outcome)->toBe('success')->and($first->new_values['request']['input']['search'])->toBe('test player');
    $this->get(route($route))->assertOk();
    expect(AuditLog::where('action', 'request.'.$route)->pluck('request_id')->unique())->toHaveCount(2);
})->with([
    ['admin', 'admin.students.index'],
    ['gam', 'gam.dashboard'],
    ['tabulator', 'tabulator.dashboard'],
    ['coordinator', 'profile.show'],
]);

test('account creation records its model changes without passwords tokens or file contents', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $this->actingAs($admin)->post(route('admin.operations-accounts.store'), [
        'name' => 'New GAM', 'email' => 'new-gam@example.test', 'password' => 'local-test-very-secret', 'password_confirmation' => 'local-test-very-secret',
        'role' => 'gam', 'status' => 'active', 'access_token' => 'never-log-this-token', 'sheet_image' => 'data:image/png;base64,private-image', 'snapshot' => json_encode(['data' => ['password' => 'nested-password-never-log']]),
    ])->assertRedirect();
    $log = AuditLog::where('action', 'request.admin.operations-accounts.store')->firstOrFail();
    $details = $log->new_values;
    expect($details['changes'])->toHaveCount(1)
        ->and($details['changes'][0]['type'])->toBe('User')
        ->and($details['changes'][0]['after']['email'])->toBe('new-gam@example.test')
        ->and($details['changes'][0]['after']['password'])->toBe('[redacted]');
    $raw = $log->getRawOriginal('new_values');
    expect($raw)->not->toContain('local-test-very-secret')->not->toContain('never-log-this-token')->not->toContain('private-image')->not->toContain('$2y$')->not->toContain('nested-password-never-log');
    $this->get(route('admin.students.index'))->assertOk();
    expect(AuditLog::where('action', 'request.admin.students.index')->firstOrFail()->new_values['changes'])->toBe([]);
});

test('gam roster and medical changes are captured even without an explicit controller audit call', function () {
    [$edition, $team, $event, $student] = activityFixture();
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $this->actingAs($gam)->put(route('gam.teams.players.sports.update', [$team, $student]), ['edition_sport_ids' => [$event->id]])->assertRedirect();
    $entry = AthleteEntry::where('student_id', $student->id)->firstOrFail();
    $this->post(route('gam.medical-certificates.update', $entry), ['medical_certificate_status' => 'verified'])->assertRedirect();
    $log = AuditLog::where('action', 'request.gam.medical-certificates.update')->firstOrFail();
    $change = collect($log->new_values['changes'])->firstWhere('type', 'AthleteEntry');
    expect($log->actor_role)->toBe('gam')->and($change['before']['medical_certificate_status'])->toBe('pending')
        ->and($change['after']['medical_certificate_status'])->toBe('verified');
});

test('tabulator winner declarations and unsuccessful scoring attempts are both recorded', function () {
    [$edition, $team, $event] = activityFixture();
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active', 'password' => Hash::make('score-test-password')]);
    $this->actingAs($tabulator)->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $team->id], 'tabulator_password' => 'score-test-password'])->assertRedirect();
    $success = AuditLog::where('action', 'request.tabulator.sport-results.store')->firstOrFail();
    expect($success->outcome)->toBe('success')->and(collect($success->new_values['changes'])->pluck('type'))->toContain('SportResult', 'TeamTally');
    $before = SportResult::firstOrFail()->placements_json;
    $this->post(route('tabulator.sport-results.store'), ['edition_sport_id' => $event->id, 'placements' => [1 => $team->id], 'tabulator_password' => 'wrong-secret'])->assertSessionHasErrors('tabulator_password');
    $failure = AuditLog::where('action', 'request.tabulator.sport-results.store')->orderByDesc('id')->firstOrFail();
    expect($failure->outcome)->toBe('failed')->and($failure->new_values['validation_fields'])->toContain('tabulator_password')
        ->and($failure->getRawOriginal('new_values'))->not->toContain('wrong-secret')
        ->and(SportResult::firstOrFail()->placements_json)->toBe($before);
});

test('manual action logs and the request summary share a request id', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $this->actingAs($admin)->post(route('admin.courses.store'), ['name' => 'Audit course'])->assertRedirect();
    $domain = AuditLog::where('action', 'course.created')->firstOrFail();
    $summary = AuditLog::where('action', 'request.admin.courses.store')->firstOrFail();
    expect($domain->request_id)->toBe($summary->request_id);
});

test('denied requests and server exceptions are recorded without exposing exception messages', function () {
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active']);
    $this->actingAs($gam)->get(route('admin.system-logs.index'))->assertForbidden();
    expect(AuditLog::where('action', 'request.admin.system-logs.index')->firstOrFail()->outcome)->toBe('denied');
    Route::middleware('web')->get('/activity-test-error', function () {
        throw new RuntimeException('do-not-log-this-private-exception-value');
    })->name('activity-test.error');
    $this->get('/activity-test-error')->assertStatus(500);
    $log = AuditLog::where('action', 'request.activity-test.error')->firstOrFail();
    expect($log->outcome)->toBe('failed')->and($log->getRawOriginal('new_values'))->not->toContain('do-not-log-this-private-exception-value');
});

test('login failure login success and logout record the actual authentication outcome and actor', function () {
    $user = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('login-test-password')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-login-secret'])->assertSessionHasErrors('email');
    expect(AuditLog::where('action', 'auth.failed')->firstOrFail()->outcome)->toBe('denied');
    $this->post('/login', ['email' => $user->email, 'password' => 'login-test-password'])->assertRedirect();
    $login = AuditLog::where('action', 'auth.login')->firstOrFail();
    expect($login->user_id)->toBe($user->id)->and($login->outcome)->toBe('success');
    $this->post('/logout')->assertRedirect();
    expect(AuditLog::where('action', 'auth.logout')->firstOrFail()->user_id)->toBe($user->id);
    $raw = DB::table('audit_logs')->get()->toJson();
    expect($raw)->not->toContain('wrong-login-secret')->not->toContain('login-test-password');
});

test('an audit storage outage cannot turn a successful save into a server error', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    Log::spy();
    DB::partialMock()->shouldReceive('table')->with('audit_logs')->andThrow(new RuntimeException('private SQL content'));
    Route::middleware('web')->post('/activity-test-storage', function () {
        app(AuditService::class)->record('test.audit.unavailable');

        return response()->json(['saved' => true]);
    })->name('activity-test.storage');
    $this->actingAs($admin)->post('/activity-test-storage')->assertOk()->assertJson(['saved' => true]);
    Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Activity log could not be stored.' && ! isset($context['sql']))->twice();
});

test('password reset tokens in route parameters never appear in activity logs', function () {
    $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    Route::middleware('web')->get('/activity-reset/{token}', fn () => response('ok'))->name('activity-test.reset');
    $this->actingAs($user)->get('/activity-reset/secret-reset-token-123')->assertOk();
    $log = AuditLog::where('action', 'request.activity-test.reset')->firstOrFail();
    expect($log->getRawOriginal('new_values'))->not->toContain('secret-reset-token-123')
        ->and($log->new_values['request']['parameters']['token'])->toBe('[redacted]');
});

test('archiving restoring and deletion record the original and final state', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $student = Student::factory()->create();
    Route::middleware('web')->post('/activity-test-lifecycle/{student}', function (Student $student) {
        $student->delete();
        $student->restore();
        $student->forceDelete();

        return response('ok');
    })->name('activity-test.lifecycle');
    $this->actingAs($admin)->post('/activity-test-lifecycle/'.$student->id)->assertOk();
    $changes = collect(AuditLog::where('action', 'request.activity-test.lifecycle')->firstOrFail()->new_values['changes']);
    expect($changes->pluck('event')->all())->toBe(['deleted', 'restored', 'forceDeleted'])
        ->and($changes[0]['before']['deleted_at'])->toBeNull()
        ->and($changes[0]['after']['deleted_at'])->not->toBeNull()
        ->and($changes[1]['before']['deleted_at'])->not->toBeNull()
        ->and($changes[1]['after']['deleted_at'])->toBeNull()
        ->and($changes[2]['after'])->toBeNull();
});

test('authenticated API requests are logged alongside web activity', function () {
    $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $this->actingAs($user)->getJson('/api/user')->assertOk();
    $log = AuditLog::where('action', 'request.get.api/user')->firstOrFail();
    expect($log->user_id)->toBe($user->id)->and($log->outcome)->toBe('success')
        ->and($log->new_values['request']['path'])->toBe('/api/user');
});
