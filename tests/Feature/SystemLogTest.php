<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('administrators can browse and filter system activity logs', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    DB::table('audit_logs')->insert([
        ['user_id' => $admin->id, 'actor_role' => 'admin', 'action' => 'student.created', 'auditable_type' => 'App\\Models\\Student', 'auditable_id' => 1, 'outcome' => 'success', 'request_id' => (string) Str::uuid(), 'created_at' => now()],
        ['user_id' => $coordinator->id, 'actor_role' => 'coordinator', 'action' => 'result.submitted', 'auditable_type' => 'App\\Models\\Event', 'auditable_id' => 1, 'outcome' => 'success', 'request_id' => (string) Str::uuid(), 'created_at' => now()],
    ]);

    $this->actingAs($admin)->get(route('admin.system-logs.index', ['role' => 'admin']))
        ->assertOk()->assertSee('Student created')->assertDontSee('Result submission submitted');
});

test('coordinators cannot browse system activity logs', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get(route('admin.system-logs.index'))->assertForbidden();
});

test('logs can filter gam and tabulator and search actors and change details', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'name' => 'Searchable GAM']);
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    app(\App\Services\AuditService::class)->record('medical.reviewed', newValues: ['player' => 'Find This Player'], actor: $gam);
    app(\App\Services\AuditService::class)->record('sport.winners', actor: $tabulator);
    $this->actingAs($admin)->get(route('admin.system-logs.index', ['role' => 'gam', 'search' => 'Find This Player']))
        ->assertOk()->assertSee('Medical review updated')->assertSee('Find This Player')->assertDontSee('Event winners declared');
    $this->get(route('admin.system-logs.index', ['role' => 'tabulator']))->assertOk()->assertSee('Event winners declared')->assertDontSee('Medical review updated');
    $this->get(route('admin.system-logs.index', ['search' => 'Searchable GAM']))->assertOk()->assertSee('Medical review updated');
});

test('log date filters use the displayed local day and legacy values are redacted', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    DB::table('audit_logs')->insert([
        ['action' => 'student.created', 'auditable_type' => 'Student', 'created_at' => '2026-10-18 16:30:00', 'new_values' => json_encode(['first_name' => 'Local', 'last_name' => 'Start', 'password' => 'legacy-secret-do-not-show']), 'outcome' => 'success'],
        ['action' => 'student.created', 'auditable_type' => 'Student', 'created_at' => '2026-10-18 15:59:00', 'new_values' => json_encode(['first_name' => 'Previous', 'last_name' => 'Day']), 'outcome' => 'success'],
    ]);
    $response = $this->actingAs($admin)->get(route('admin.system-logs.index', ['from' => '2026-10-19', 'to' => '2026-10-19']));
    expect($response->viewData('logs')->pluck('presentation.subject')->all())->toBe(['Local Start']);
    $response
        ->assertOk()->assertSee('Local Start')->assertDontSee('Previous Day')->assertDontSee('legacy-secret-do-not-show')->assertDontSee('[redacted]');
    $this->get(route('admin.system-logs.index', ['from' => 'not-a-date']))->assertSessionHasErrors('from');
});
