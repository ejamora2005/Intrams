<?php

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;

test('an administrator can open a downloadable basketball score sheet with registered rosters', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Basketball Score Sheet Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Basketball 5x5', 'code' => 'BASKETBALL-5X5', 'status' => 'active']);
    $editionSport = EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);
    $team = Team::query()->create(['edition_id' => $edition->id, 'name' => 'BLUE WARRIORS', 'code' => 'BLUEWARRIORS', 'status' => 'active']);
    $student = Student::factory()->create(['status' => 'active', 'first_name' => 'Jordan', 'last_name' => 'Rivera', 'year_level' => '3rd', 'section' => 'B']);
    AthleteEntry::query()->create([
        'edition_sport_id' => $editionSport->id,
        'team_id' => $team->id,
        'student_id' => $student->id,
        'status' => 'active',
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('FEDERATION INTERNATIONALE DE BASKETBALL')
        ->assertSee('images/fiba-basketball.webp')
        ->assertSee('BLUE WARRIORS')
        ->assertSee('3rd - B')
        ->assertSee('RUNNING SCORE')
        ->assertSee('score-entry')
        ->assertSee('placeholder="1"', false)
        ->assertSee('.fiba-roster .player-no { text-align: center; width: 5px; }', false)
        ->assertSee('.fiba-roster .fouls { width: 25px; }', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('Preview &amp; Download PDF', false)
        ->assertDontSee('Print score sheet');
});

test('the basketball score sheet is unavailable for non-basketball sports', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Non Basketball Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Volleyball', 'code' => 'VOLLEYBALL', 'status' => 'active']);
    EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertNotFound();
});

test('an administrator can preview the completed basketball score sheet as a one-page A4 PDF', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Download Score Sheet Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Basketball 3x3', 'code' => 'BASKETBALL-3X3', 'status' => 'active']);
    EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.sports.basketball-score-sheet.download', $sport), [
        'edition_id' => $edition->id,
        'team_a_name' => 'BLUE WARRIORS',
        'team_b_name' => 'RED HAWKS',
        'final_score_a' => '21',
        'final_score_b' => '18',
    ]);

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename=basketball-score-sheet-'.$edition->id.'.pdf');

    expect($response->getContent())
        ->toStartWith('%PDF')
        ->and(preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()))->toBe(1);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'basketball_score_sheet.previewed']);
});
