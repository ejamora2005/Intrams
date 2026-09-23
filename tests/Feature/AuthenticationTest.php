<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('active administrators and coordinators can authenticate using the shared login screen', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
})->with(['administrator' => 'admin', 'coordinator' => 'coordinator']);

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('inactive or unsupported accounts cannot authenticate', function (array $attributes) {
    $user = User::factory()->create($attributes);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
})->with([
    'inactive coordinator' => [['role' => 'coordinator', 'status' => 'suspended']],
    'unsupported role' => [['role' => 'student', 'status' => 'active']],
]);

test('administrators can access the admin dashboard and its sidebar modules', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertOk()
        ->assertSee('Program overview')
        ->assertSee('Students')
        ->assertSee('Events / Editions')
        ->assertSee('Live Competition')
        ->assertSee('System Logs');
});

test('coordinators cannot access admin modules', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($coordinator)->get('/admin/students')->assertForbidden();
});
