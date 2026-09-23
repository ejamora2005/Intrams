<?php

use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\User;

function sportsAdmin(): User { return User::factory()->create(['role' => 'admin', 'status' => 'active']); }
function sportsEdition(): IntramuralEdition { return IntramuralEdition::create(['name' => 'Sports Edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-31', 'status' => 'active']); }

test('admin can manage sports and create a draft event', function () {
    $admin = sportsAdmin();
    $edition = sportsEdition();
    $this->actingAs($admin)->post(route('admin.sports.store'), ['name' => 'basketball', 'code' => 'bball', 'description' => null, 'status' => 'active'])->assertRedirect();
    $sport = Sport::firstOrFail();
    $this->assertDatabaseHas('sports', ['name' => 'Basketball']);
    expect($sport->code)->toStartWith('SPORT-');
    $this->actingAs($admin)->post(route('admin.events.store'), ['edition_id' => $edition->id, 'sport_id' => $sport->id, 'name' => 'Boys Basketball', 'code' => 'BKB', 'competition_type' => 'team', 'division' => 'Boys', 'venue' => 'Gym', 'capacity' => 8, 'starts_at' => '2026-10-02 08:00:00', 'ends_at' => '2026-10-02 10:00:00', 'result_mode' => 'score'])->assertRedirect();
    $this->assertDatabaseHas('events', ['edition_id' => $edition->id, 'name' => 'Sports Edition - Basketball - Team', 'venue' => null, 'starts_at' => null, 'ends_at' => null, 'status' => 'draft']);
    expect(App\Models\Event::firstOrFail()->code)->toStartWith('EVT-');
    $this->assertDatabaseHas('audit_logs', ['action' => 'event.created']);
});

test('event lifecycle rejects invalid transition', function () {
    $admin = sportsAdmin(); $edition = sportsEdition(); $sport = Sport::create(['name' => 'Volleyball', 'code' => 'VB', 'status' => 'active']);
    $event = App\Models\Event::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'name' => 'Volleyball', 'code' => 'VB1', 'competition_type' => 'team', 'status' => 'draft', 'result_mode' => 'score']);
    $this->actingAs($admin)->put(route('admin.events.update', $event), ['edition_id' => $edition->id, 'sport_id' => $sport->id, 'name' => 'Volleyball', 'code' => 'VB1', 'competition_type' => 'team', 'result_mode' => 'score', 'status' => 'completed'])->assertStatus(422);
});
