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
        ->assertOk()->assertSee('student.created')->assertDontSee('result.submitted');
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
        ->assertOk()->assertSee('medical.reviewed')->assertDontSee('sport.winners');
    $this->get(route('admin.system-logs.index', ['role' => 'tabulator']))->assertOk()->assertSee('sport.winners')->assertDontSee('medical.reviewed');
    $this->get(route('admin.system-logs.index', ['search' => 'Searchable GAM']))->assertOk()->assertSee('medical.reviewed');
});

test('log date filters use the displayed local day and legacy values are redacted', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    DB::table('audit_logs')->insert([
        ['action' => 'local.day.start', 'auditable_type' => 'User', 'created_at' => '2026-10-18 16:30:00', 'new_values' => json_encode(['password' => 'legacy-secret-do-not-show']), 'outcome' => 'success'],
        ['action' => 'previous.day', 'auditable_type' => 'User', 'created_at' => '2026-10-18 15:59:00', 'new_values' => null, 'outcome' => 'success'],
    ]);
    $this->actingAs($admin)->get(route('admin.system-logs.index', ['from' => '2026-10-19', 'to' => '2026-10-19']))
        ->assertOk()->assertSee('local.day.start')->assertDontSee('previous.day')->assertDontSee('legacy-secret-do-not-show')->assertSee('[redacted]');
    $this->get(route('admin.system-logs.index', ['from' => 'not-a-date']))->assertSessionHasErrors('from');
});
