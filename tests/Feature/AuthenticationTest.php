<?php

use App\Models\CompetitionSchedule;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\ScheduleParticipant;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Carbon;

test('public landing page uses the intramurals design system', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('INTRAMURALS 2026: STUDENT FESTIVAL')
        ->assertSee('Compete. Create. Lead. Connect. Express.')
        ->assertSee('Intramural Meet')
        ->assertSee('Schedule')
        ->assertSee('images/logo/sea_dragons.png', false)
        ->assertSee('images/logo/terraquatic_eagles.png', false)
        ->assertSee('images/logo/trojan_warriors.png', false)
        ->assertSee('rel="shortcut icon"', false)
        ->assertSee('team-banner', false);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('SLSU INTRAMURALS')
        ->assertSee('rel="shortcut icon"', false)
        ->assertSee('Download SLSU INTRAMURALS')
        ->assertSee('INTRAMURALS 2026: STUDENT FESTIVAL')
        ->assertSee('Compete. Create. Lead. Connect. Express.')
        ->assertSee('class="space-y-3 lg:hidden"', false)
        ->assertSee('max-h-[calc(100dvh-1rem)]', false);
});

test('login page shows a manually refreshed snapshot of todays competition schedule', function () {
    Carbon::setTestNow('2026-09-27 09:00:00');

    try {
        $edition = IntramuralEdition::query()->create([
            'name' => '2026 SLSU Intramurals',
            'school_year' => '2026-2027',
            'starts_on' => '2026-09-27',
            'ends_on' => '2026-09-30',
            'status' => 'active',
        ]);
        $sport = Sport::query()->create(['name' => 'Volleyball', 'code' => 'VOLLEYBALL', 'status' => 'active']);
        $editionSport = EditionSport::query()->create([
            'edition_id' => $edition->id,
            'sport_id' => $sport->id,
            'participant_type' => 'team',
            'game_mechanic' => 'single_elimination',
            'status' => 'active',
        ]);
        $home = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Blue Spikers', 'code' => 'BLUE', 'status' => 'active']);
        $visitor = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Red Smashers', 'code' => 'RED', 'status' => 'active']);
        $afternoonHome = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Green Servers', 'code' => 'GREEN', 'status' => 'active']);
        $afternoonVisitor = Team::query()->create(['edition_id' => $edition->id, 'name' => 'Gold Blockers', 'code' => 'GOLD', 'status' => 'active']);
        $coordinator = User::factory()->create(['name' => 'Jamie Facilitator', 'role' => 'coordinator', 'status' => 'active']);
        $morningBracketMatch = BracketMatch::query()->create(['edition_sport_id' => $editionSport->id, 'bracket' => 'winners', 'round_number' => 1, 'match_number' => 1, 'status' => 'pending']);
        $schedule = CompetitionSchedule::query()->create([
            'edition_sport_id' => $editionSport->id,
            'bracket_match_id' => $morningBracketMatch->id,
            'starts_at' => '2026-09-27 09:30:00',
            'ends_at' => '2026-09-27 11:00:00',
            'venue' => 'Main Court',
            'status' => 'scheduled',
            'coordinator_id' => $coordinator->id,
        ]);
        ScheduleParticipant::query()->create(['competition_schedule_id' => $schedule->id, 'team_id' => $home->id, 'slot' => 'A', 'status' => 'active']);
        ScheduleParticipant::query()->create(['competition_schedule_id' => $schedule->id, 'team_id' => $visitor->id, 'slot' => 'B', 'status' => 'active']);
        $afternoonBracketMatch = BracketMatch::query()->create(['edition_sport_id' => $editionSport->id, 'bracket' => 'winners', 'round_number' => 1, 'match_number' => 2, 'status' => 'pending']);
        $afternoonSchedule = CompetitionSchedule::query()->create([
            'edition_sport_id' => $editionSport->id,
            'bracket_match_id' => $afternoonBracketMatch->id,
            'starts_at' => '2026-09-27 14:00:00',
            'ends_at' => '2026-09-27 15:30:00',
            'status' => 'scheduled',
            'coordinator_id' => $coordinator->id,
        ]);
        ScheduleParticipant::query()->create(['competition_schedule_id' => $afternoonSchedule->id, 'team_id' => $afternoonHome->id, 'slot' => 'A', 'status' => 'active']);
        ScheduleParticipant::query()->create(['competition_schedule_id' => $afternoonSchedule->id, 'team_id' => $afternoonVisitor->id, 'slot' => 'B', 'status' => 'active']);

        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('2026 SLSU Intramurals')
            ->assertSee('September 27, 2026')
            ->assertSee('Volleyball')
            ->assertSee('Morning')
            ->assertSeeInOrder(['Game 1', 'Blue Spikers VS Red Smashers'])
            ->assertSee('Main Court')
            ->assertSee('Blue Spikers VS Red Smashers')
            ->assertSee('Afternoon')
            ->assertSeeInOrder(['Game 2', 'Green Servers VS Gold Blockers'])
            ->assertSee('TBA')
            ->assertSee('Green Servers VS Gold Blockers')
            ->assertSee('Jamie Facilitator')
            ->assertSee('<th scope="rowgroup" rowspan="2"', false)
            ->assertSee('data-open-login', false)
            ->assertSee('data-login-modal', false)
            ->assertSee('backdrop:backdrop-blur-md', false)
            ->assertSee('Updates appear as coordinator entries are published.')
            ->assertDontSee('wire:poll', false)
            ->assertDontSee('setInterval(', false);

        expect(substr_count($response->getContent(), '>Volleyball</th>'))->toBe(1);
    } finally {
        Carbon::setTestNow();
    }
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
        ->assertSee('SLSU')
        ->assertSee('INTRAMURALS')
        ->assertSee('INTRAMURALS 2026: STUDENT FESTIVAL')
        ->assertSee('Compete. Create. Lead. Connect. Express.')
        ->assertSee('SLSU INTRAMURALS dashboard')
        ->assertSee('data-open-admin-sidebar', false)
        ->assertSee('data-admin-sidebar-backdrop', false)
        ->assertSee('aria-controls="admin-sidebar"', false)
        ->assertSee('Students')
        ->assertSee('Events / Editions')
        ->assertSee('Live Competition')
        ->assertSee('System Logs');
});

test('default account seeding does not log out active sessions when the password is unchanged', function () {
    $previous = $_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] ?? null;
    putenv('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD=password');
    $_ENV['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = 'password';
    $_SERVER['INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD'] = 'password';

    try {
        $this->seed(AdminUserSeeder::class);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(RouteServiceProvider::HOME);

        $this->get('/admin/dashboard')->assertOk();

        $this->seed(AdminUserSeeder::class);

        $this->get('/admin/dashboard')->assertOk();
    } finally {
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

test('coordinators cannot access admin modules', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($coordinator)->get('/admin/students')->assertForbidden();
});
