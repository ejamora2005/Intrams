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
