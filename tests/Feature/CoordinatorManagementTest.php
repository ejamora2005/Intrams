<?php

use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorDevice;
use App\Models\CoordinatorRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\CoordinatorDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function coordinatorModuleAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function coordinatorModuleEvent(): Event
{
    $editionId = DB::table('intramural_editions')->insertGetId([
        'name' => 'Intramurals 2026', 'school_year' => '2026-2027', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $sportId = DB::table('sports')->insertGetId([
        'name' => 'Basketball', 'code' => 'BASKETBALL', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return Event::query()->create([
        'edition_id' => $editionId, 'sport_id' => $sportId, 'name' => 'Boys Basketball', 'code' => 'BB-M', 'competition_type' => 'team', 'status' => 'draft', 'result_mode' => 'score',
    ]);
}

test('admin can create and manage a coordinator account', function () {
    $admin = coordinatorModuleAdmin();

    $this->actingAs($admin)->post(route('admin.coordinators.store'), [
        'name' => 'Jordan Cruz', 'email' => 'jordan@example.com', 'password' => 'secure-password', 'password_confirmation' => 'secure-password', 'status' => 'active',
    ])->assertRedirect();

    $coordinator = User::query()->where('email', 'jordan@example.com')->firstOrFail();
    expect($coordinator->role)->toBe('coordinator');
    $this->assertDatabaseHas('audit_logs', ['action' => 'coordinator.created', 'user_id' => $admin->id]);
});

test('admin can assign and revoke an event for a coordinator', function () {
    $admin = coordinatorModuleAdmin();
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $event = coordinatorModuleEvent();

    $this->actingAs($admin)->post(route('admin.coordinators.assignments.store', $coordinator), ['event_id' => $event->id])->assertRedirect();
    $assignment = CoordinatorAssignment::query()->firstOrFail();
    expect($assignment->status)->toBe('active');

    $this->actingAs($admin)->delete(route('admin.coordinators.assignments.destroy', [$coordinator, $assignment]))->assertRedirect();
    expect($assignment->fresh()->status)->toBe('revoked');
});

test('coordinator can request an assignment and admin can approve it', function () {
    $admin = coordinatorModuleAdmin();
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $event = coordinatorModuleEvent();

    $this->actingAs($coordinator)->post(route('coordinator.requests.store'), [
        'event_id' => $event->id, 'request_type' => 'add_event', 'reason' => 'I am available for this event.',
    ])->assertRedirect();
    $request = CoordinatorRequest::query()->firstOrFail();
    expect($request->status)->toBe('pending');

    $this->actingAs($admin)->post(route('admin.coordinator-requests.review', $request), ['decision' => 'approved'])->assertRedirect();
    expect($request->fresh()->status)->toBe('approved');
    $this->assertDatabaseHas('coordinator_assignments', ['coordinator_id' => $coordinator->id, 'event_id' => $event->id, 'status' => 'active']);
});

test('approved reassignment revokes the source event and assigns the target event', function () {
    $admin = coordinatorModuleAdmin();
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $sourceEvent = coordinatorModuleEvent();
    $targetEvent = Event::query()->create([
        'edition_id' => $sourceEvent->edition_id, 'sport_id' => $sourceEvent->sport_id, 'name' => 'Girls Basketball', 'code' => 'BB-W', 'competition_type' => 'team', 'status' => 'draft', 'result_mode' => 'score',
    ]);
    CoordinatorAssignment::query()->create(['coordinator_id' => $coordinator->id, 'event_id' => $sourceEvent->id, 'assigned_by' => $admin->id, 'status' => 'active', 'approved_at' => now()]);

    $this->actingAs($coordinator)->post(route('coordinator.requests.store'), [
        'event_id' => $targetEvent->id, 'source_event_id' => $sourceEvent->id, 'request_type' => 'reassign', 'reason' => 'I need to move to the girls division.',
    ])->assertRedirect();
    $request = CoordinatorRequest::query()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.coordinator-requests.review', $request), ['decision' => 'approved'])->assertRedirect();
    $this->assertDatabaseHas('coordinator_assignments', ['coordinator_id' => $coordinator->id, 'event_id' => $sourceEvent->id, 'status' => 'revoked']);
    $this->assertDatabaseHas('coordinator_assignments', ['coordinator_id' => $coordinator->id, 'event_id' => $targetEvent->id, 'status' => 'active']);
});

test('coordinator account can register one trusted device and requires it on later login', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active', 'password' => bcrypt('password')]);

    $firstLogin = $this->post('/login', ['email' => $coordinator->email, 'password' => 'password']);
    $firstLogin->assertRedirect('/dashboard')->assertCookie('ims_coordinator_device');
    $device = CoordinatorDevice::query()->firstOrFail();
    expect($device->user_id)->toBe($coordinator->id);

    $newDeviceRequest = Request::create('/login', 'POST');
    $newDeviceRequest->cookies->set('ims_coordinator_device', 'different-device-token');
    $verification = app(CoordinatorDeviceService::class)->isAllowedForLogin($coordinator, $newDeviceRequest);
    expect($verification)->toBeFalse();
});

test('admin device reset allows a coordinator to register another trusted device', function () {
    $admin = coordinatorModuleAdmin();
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $device = CoordinatorDevice::query()->create(['user_id' => $coordinator->id, 'token_hash' => hash('sha256', 'old-token'), 'registered_at' => now(), 'last_seen_at' => now()]);

    $this->actingAs($admin)->post(route('admin.coordinators.device.reset', $coordinator))->assertRedirect();
    expect($device->fresh()->revoked_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['action' => 'coordinator.device.reset']);
});

test('coordinators cannot access coordinator management pages', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $this->actingAs($coordinator)->get(route('admin.coordinators.index'))->assertForbidden();
});
