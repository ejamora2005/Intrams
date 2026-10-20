<?php

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\BracketMatch;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamTally;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function medicalApprovalFixture(): array
{
    $edition = IntramuralEdition::create(['name' => 'Medical edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA-DRAGONS', 'status' => 'active']);
    $student = Student::factory()->create(['first_name' => 'Mara', 'last_name' => 'Review', 'status' => 'active', 'gender' => 'Female']);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
    $entries = collect([
        ['Volleyball (Women)', 'VOLLEYBALL-WOMEN', 'sports_major'],
        ['100m Run (Women)', '100M-WOMEN', 'sports_athletics'],
        ['Chess (Women)', 'CHESS-WOMEN', 'sports_minor'],
        ['Painting', 'PAINTING', 'cultural_individual'],
    ])->map(function ($definition) use ($edition, $student, $team) {
        [$name, $code, $pointSystem] = $definition;
        $sport = Sport::create(['name' => $name, 'code' => $code, 'status' => 'active']);
        $event = $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'status' => 'active', 'scoring_rules' => ['point_system' => $pointSystem]]);

        return AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $team->id, 'status' => 'active', 'assigned_at' => now(), 'medical_certificate_status' => 'pending']);
    });

    return [$edition, $team, $student, $admin, $entries];
}

function medicalApprovalPayload($test, Student $student, IntramuralEdition $edition, array $entryIds, string $status = 'verified'): array
{
    $version = $test->get(route('admin.medical-approvals.edit', $student))->assertOk()->viewData('version');

    return ['edition_id' => $edition->id, 'student_id' => $student->id, 'entry_ids' => $entryIds, 'version' => $version, 'medical_certificate_status' => $status, 'medical_certificate_notes' => 'Certificate reviewed'];
}

test('medical approvals are admin only across every CRUD endpoint', function (string $role) {
    [$edition, , $student] = medicalApprovalFixture();
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);
    $this->actingAs($user);
    foreach (['index', 'create', 'show', 'edit'] as $action) {
        $this->get(route('admin.medical-approvals.'.$action, in_array($action, ['show', 'edit']) ? $student : []))->assertForbidden();
    }
    $this->post(route('admin.medical-approvals.store'), [])->assertForbidden();
    $this->put(route('admin.medical-approvals.update', $student), [])->assertForbidden();
    $this->delete(route('admin.medical-approvals.destroy', $student), [])->assertForbidden();
})->with(['gam', 'tabulator', 'coordinator']);

test('admin can browse search and filter current medical clearances without showing exempt events', function () {
    [$edition, $team, $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin)->get(route('admin.medical-approvals.index'))->assertOk()
        ->assertSee('Medical Approvals')->assertSee($student->full_name)->assertSee('Volleyball (Women)')->assertSee('Sea Dragons')
        ->assertDontSee('Chess (Women)')->assertDontSee('Painting');
    $this->get(route('admin.medical-approvals.index', ['search' => '100m', 'team_id' => $team->id, 'status' => 'pending']))->assertOk()->assertSee($student->full_name);
    $this->get(route('admin.medical-approvals.index', ['search' => 'missing player']))->assertOk()->assertSee('No matching medical clearances');
    $this->get(route('admin.medical-approvals.create'))->assertOk()->assertSee('Choose a player');
    $this->get(route('admin.medical-approvals.create', ['student_id' => $student->id]))->assertOk()->assertViewHas('entries', fn ($rows) => $rows->count() === 2);
});

test('admin creates approved medical reviews that are reflected on GAM and audited', function () {
    [$edition, $team, $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, $entries->take(2)->pluck('id')->all());
    $this->post(route('admin.medical-approvals.store'), $payload)->assertRedirect(route('admin.medical-approvals.show', $student));
    foreach ($entries->take(2) as $entry) {
        expect($entry->fresh()->medical_certificate_status)->toBe('verified')
            ->and($entry->fresh()->medical_certificate_reviewed_by)->toBe($admin->id)
            ->and($entry->fresh()->medical_certificate_reviewed_at)->not->toBeNull();
    }
    expect($entries[2]->fresh()->medical_certificate_reviewed_by)->toBeNull();
    $this->get(route('admin.medical-approvals.show', $student))->assertOk()->assertSee('Approved')->assertSee($admin->name)->assertSee('UTC+8');
    expect(AuditLog::where('action', 'medical_approval.created')->where('user_id', $admin->id)->exists())->toBeTrue();
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    $response = $this->actingAs($gam)->get(route('gam.dashboard'))->assertOk()->assertSee('Verified');
    expect($response->viewData('medicalCertificateEntries')->pluck('medical_certificate_notes')->unique()->all())->toBe(['Certificate reviewed']);
});

test('medical review edits affect only selected events and preserve registrations scores and winners', function () {
    [$edition, $team, $student, $admin, $entries] = medicalApprovalFixture();
    $event = $entries[0]->editionSport;
    BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'completed', 'winner_team_id' => $team->id]);
    SportResult::create(['edition_sport_id' => $event->id, 'placements_json' => [1 => $team->id], 'points_json' => [1 => 20], 'submitted_by' => $admin->id, 'submitted_at' => now()]);
    TeamTally::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'points' => 20, 'gold_count' => 1]);
    $tables = ['students', 'team_members', 'bracket_matches', 'sport_results', 'team_tallies'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    $before = $snapshot();
    $entryBefore = $entries[0]->only(['edition_sport_id', 'student_id', 'team_id', 'status', 'pair_key']);
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id], 'rejected');
    $this->put(route('admin.medical-approvals.update', $student), $payload)->assertRedirect();
    expect($entries[0]->fresh()->medical_certificate_status)->toBe('rejected')
        ->and($entries[1]->fresh()->medical_certificate_status)->toBe('pending')
        ->and($entries[0]->fresh()->only(array_keys($entryBefore)))->toBe($entryBefore)
        ->and($snapshot())->toBe($before);
    $this->get(route('admin.medical-approvals.index', ['status' => 'rejected']))->assertSee($student->full_name)->assertSee('Mixed statuses');
    expect(AuditLog::where('action', 'medical_approval.updated')->exists())->toBeTrue();
});

test('removing a medical review requires confirmation and resets only chosen clearance fields', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, $entries->take(2)->pluck('id')->all());
    $this->post(route('admin.medical-approvals.store'), $payload)->assertRedirect();
    $delete = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id]);
    $this->delete(route('admin.medical-approvals.destroy', $student), $delete)->assertSessionHasErrors('confirmation');
    $this->delete(route('admin.medical-approvals.destroy', $student), [...$delete, 'confirmation' => 'REMOVE'])->assertRedirect();
    expect($entries[0]->fresh()->medical_certificate_status)->toBe('pending')
        ->and($entries[0]->fresh()->medical_certificate_notes)->toBeNull()
        ->and($entries[0]->fresh()->medical_certificate_reviewed_by)->toBeNull()
        ->and($entries[0]->fresh()->medical_certificate_reviewed_at)->toBeNull()
        ->and($entries[0]->fresh()->status)->toBe('active')
        ->and($entries[1]->fresh()->medical_certificate_status)->toBe('verified');
    $this->assertDatabaseHas('team_members', ['student_id' => $student->id]);
    expect(AuditLog::where('action', 'medical_approval.deleted')->exists())->toBeTrue();
});

test('medical approval rejects stale GAM changes and does not overwrite the latest decision', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id]);
    $entries[0]->update(['medical_certificate_status' => 'rejected', 'medical_certificate_notes' => 'Latest GAM decision']);
    $this->put(route('admin.medical-approvals.update', $student), $payload)->assertSessionHasErrors('review');
    expect($entries[0]->fresh()->medical_certificate_notes)->toBe('Latest GAM decision');
});

test('medical approval rejects foreign exempt and inactive registrations atomically', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $otherStudent = Student::factory()->create(['status' => 'active']);
    $foreign = AthleteEntry::create(['edition_sport_id' => $entries[0]->edition_sport_id, 'student_id' => $otherStudent->id, 'status' => 'active']);
    $this->actingAs($admin);
    foreach ([$foreign->id, $entries[2]->id, $entries[3]->id] as $invalid) {
        $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id, $invalid]);
        $this->post(route('admin.medical-approvals.store'), $payload)->assertSessionHasErrors('entry_ids');
        expect($entries[0]->fresh()->medical_certificate_status)->toBe('pending');
    }
    $entries[1]->update(['status' => 'inactive']);
    $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id, $entries[1]->id]);
    $this->put(route('admin.medical-approvals.update', $student), $payload)->assertSessionHasErrors('entry_ids');
});

test('creating a review cannot overwrite an existing review and pending reviews have a record', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id], 'pending');
    $payload['medical_certificate_notes'] = '';
    $this->post(route('admin.medical-approvals.store'), $payload)->assertRedirect();
    expect($entries[0]->fresh()->medical_certificate_reviewed_by)->toBe($admin->id);
    $payload = medicalApprovalPayload($this, $student, $edition, [$entries[0]->id]);
    $this->post(route('admin.medical-approvals.store'), $payload)->assertSessionHasErrors('entry_ids');
    expect($entries[0]->fresh()->medical_certificate_status)->toBe('pending');
});

test('archived students and deleted sports cannot break the medical approvals module', function () {
    [, , $student, $admin, $entries] = medicalApprovalFixture();
    $entries[1]->editionSport->sport->delete();
    $this->actingAs($admin)->get(route('admin.medical-approvals.index'))->assertOk()->assertDontSee('100m Run (Women)');
    $student->delete();
    $this->get(route('admin.medical-approvals.index'))->assertOk()->assertDontSee($student->full_name);
    $this->get(route('admin.medical-approvals.show', $student))->assertNotFound();
});

test('medical review validates empty selections and keeps the entered decision and notes', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $this->actingAs($admin);
    $payload = medicalApprovalPayload($this, $student, $edition, [], 'rejected');
    $this->from(route('admin.medical-approvals.edit', $student))
        ->put(route('admin.medical-approvals.update', $student), $payload)
        ->assertRedirect(route('admin.medical-approvals.edit', $student))
        ->assertSessionHasErrors('entry_ids')
        ->assertSessionHasInput('medical_certificate_status', 'rejected')
        ->assertSessionHasInput('medical_certificate_notes', 'Certificate reviewed');
    expect($entries[0]->fresh()->medical_certificate_status)->toBe('pending');
});

test('medical approvals handle an inactive edition without exposing its old registrations', function () {
    [$edition, , $student, $admin, $entries] = medicalApprovalFixture();
    $edition->update(['status' => 'completed']);
    $this->actingAs($admin)->get(route('admin.medical-approvals.index'))->assertOk()->assertSee('No active intramurals edition.')->assertDontSee($student->full_name);
    $this->get(route('admin.medical-approvals.create'))->assertNotFound();
    $this->get(route('admin.medical-approvals.show', $student))->assertNotFound();
    $this->put(route('admin.medical-approvals.update', $student), [])->assertNotFound();
    expect($entries[0]->fresh()->medical_certificate_status)->toBe('pending');
});
