<?php

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

function bulkAssignmentAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function bulkAssignmentEdition(): IntramuralEdition
{
    return IntramuralEdition::query()->create([
        'name' => 'Bulk Assignment Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
}

function bulkAssignmentSport(IntramuralEdition $edition, string $type = 'team', string $mechanic = 'single_elimination'): EditionSport
{
    $sport = Sport::query()->create(['name' => 'Bulk Assignment '.ucfirst($type), 'code' => 'BULK-'.strtoupper($type), 'status' => 'active']);

    return EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => $type,
        'game_mechanic' => $mechanic,
        'status' => 'active',
    ]);
}

test('an admin can bulk-assign selected roster members to a team sport', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);
    $team = Team::query()->create(['edition_id' => $edition->id, 'name' => 'BULK TEAM', 'code' => 'BULKTEAM', 'status' => 'active']);
    $students = Student::factory()->count(2)->create(['status' => 'active']);

    foreach ($students as $student) {
        TeamMember::query()->create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    }

    $this->actingAs($admin)
        ->post(route('admin.sports.participants.store', $editionSport->sport), [
            'team_id' => $team->id,
            'student_ids' => $students->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.sports.participants', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    foreach ($students as $student) {
        $this->assertDatabaseHas('athlete_entries', ['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $team->id, 'status' => 'active']);
    }

    expect(AthleteEntry::query()->where('edition_sport_id', $editionSport->id)->count())->toBe(2);
    $this->assertDatabaseCount('audit_logs', 2);
});

test('the universal assignment page filters students by the selected team', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);
    $team = Team::query()->create(['edition_id' => $edition->id, 'name' => 'FILTER TEAM', 'code' => 'FILTERTEAM', 'status' => 'active']);
    $member = Student::factory()->create(['status' => 'active', 'first_name' => 'Included', 'last_name' => 'Student']);
    $excluded = Student::factory()->create(['status' => 'active', 'first_name' => 'Excluded', 'last_name' => 'Student']);
    TeamMember::query()->create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $member->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.sports.participants.assign', [$editionSport->sport, 'team_id' => $team->id]))
        ->assertOk()
        ->assertSee($member->full_name)
        ->assertDontSee($excluded->full_name);
});

test('an admin can create multiple tied dual pairs for one team', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition, 'dual');
    $team = Team::query()->create(['edition_id' => $edition->id, 'name' => 'DUAL TEAM', 'code' => 'DUALTEAM', 'status' => 'active']);
    $students = Student::factory()->count(4)->sequence(
        ['first_name' => 'First', 'last_name' => 'Pair One'],
        ['first_name' => 'Second', 'last_name' => 'Pair One'],
        ['first_name' => 'First', 'last_name' => 'Pair Two'],
        ['first_name' => 'Second', 'last_name' => 'Pair Two'],
    )->create(['status' => 'active']);

    foreach ($students as $student) {
        TeamMember::query()->create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    }

    $this->actingAs($admin)
        ->get(route('admin.sports.participants.assign', [$editionSport->sport, 'edition_id' => $edition->id, 'team_id' => $team->id]))
        ->assertOk()
        ->assertSee('First pair member')
        ->assertSee('Second pair member')
        ->assertSee('Add selected pair')
        ->assertSee('Search name or student number');

    foreach ($students->chunk(2) as $pair) {
        $this->actingAs($admin)
            ->post(route('admin.sports.participants.store', $editionSport->sport), [
                'edition_id' => $edition->id,
                'team_id' => $team->id,
                'student_ids' => $pair->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.sports.participants', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));
    }

    $entries = AthleteEntry::query()->where('edition_sport_id', $editionSport->id)->get();
    expect($entries)->toHaveCount(4);
    expect($entries->groupBy('pair_key'))->toHaveCount(2);
    expect($entries->groupBy('pair_key')->every(fn ($pair) => $pair->count() === 2))->toBeTrue();
    expect($entries->pluck('team_id')->unique()->all())->toBe([$team->id]);
});

test('an admin can bulk-remove selected participant registrations without affecting another team', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);
    $firstTeam = Team::query()->create(['edition_id' => $edition->id, 'name' => 'REMOVE TEAM', 'code' => 'REMOVETEAM', 'status' => 'active']);
    $secondTeam = Team::query()->create(['edition_id' => $edition->id, 'name' => 'KEEP TEAM', 'code' => 'KEEPTEAM', 'status' => 'active']);
    $students = Student::factory()->count(3)->create(['status' => 'active']);
    $firstEntry = AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $students[0]->id, 'team_id' => $firstTeam->id, 'status' => 'active', 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    $secondEntry = AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $students[1]->id, 'team_id' => $firstTeam->id, 'status' => 'active', 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    $otherTeamEntry = AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $students[2]->id, 'team_id' => $secondTeam->id, 'status' => 'active', 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.sports.participants', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Find a student')
        ->assertSee($firstTeam->name)
        ->assertSee($secondTeam->name);

    $this->actingAs($admin)
        ->from(route('admin.sports.participants', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]))
        ->post(route('admin.sports.participants.bulk-remove', $editionSport->sport), ['edition_id' => $edition->id, 'athlete_entry_ids' => [$firstEntry->id, $secondEntry->id]])
        ->assertRedirect(route('admin.sports.participants', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    $this->assertDatabaseMissing('athlete_entries', ['id' => $firstEntry->id]);
    $this->assertDatabaseMissing('athlete_entries', ['id' => $secondEntry->id]);
    $this->assertDatabaseHas('athlete_entries', ['id' => $otherTeamEntry->id, 'team_id' => $secondTeam->id]);
    expect(DB::table('audit_logs')->where('action', 'athlete_entry.removed')->count())->toBe(2);
});

test('a sport has a separate bracket page from participant management', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);

    $this->actingAs($admin)
        ->get(route('admin.sports.bracket', $editionSport->sport))
        ->assertOk()
        ->assertSee('Single Elimination')
        ->assertSee('Game Style / Elimination Style')
        ->assertSee('Tournament Name')
        ->assertSee('Manage participants');
});

test('an admin schedules a selected bracket match by date and time of day', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);
    $teams = collect(range(1, 2))->map(fn ($number) => Team::query()->create([
        'edition_id' => $edition->id,
        'name' => 'SCHEDULE TEAM '.$number,
        'code' => 'SCHEDULE'.$number,
        'status' => 'active',
    ]));

    foreach ($teams as $team) {
        $student = Student::factory()->create(['status' => 'active']);
        AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $team->id, 'status' => 'active', 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    }

    $this->actingAs($admin)
        ->get(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Schedule game')
        ->assertSee('Time of day');

    $match = BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'pending')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.sports.bracket.schedule', [$editionSport->sport, $match]), [
            'edition_id' => $edition->id,
            'date' => '2026-10-02',
            'period' => 'morning',
        ])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    $schedule = CompetitionSchedule::query()->where('bracket_match_id', $match->id)->firstOrFail();
    expect($schedule->starts_at->format('Y-m-d H:i'))->toBe('2026-10-02 08:00')
        ->and($schedule->participants()->orderBy('slot')->pluck('team_id')->all())->toBe($teams->pluck('id')->all());

    $this->actingAs($admin)
        ->post(route('admin.sports.bracket.schedule', [$editionSport->sport, $match]), [
            'edition_id' => $edition->id,
            'date' => '2026-10-03',
            'period' => 'afternoon',
        ])
        ->assertRedirect();

    expect(CompetitionSchedule::query()->where('bracket_match_id', $match->id)->count())->toBe(1)
        ->and($schedule->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-03 13:00');
});

test('declaring a bracket winner advances the team to the next matchup', function () {
    $admin = bulkAssignmentAdmin();
    $edition = bulkAssignmentEdition();
    $editionSport = bulkAssignmentSport($edition);
    $teams = collect(range(1, 4))->map(fn ($number) => Team::query()->create(['edition_id' => $edition->id, 'name' => 'TEAM '.$number, 'code' => 'TEAM'.$number, 'status' => 'active']));
    foreach ($teams as $team) {
        $student = Student::factory()->create(['status' => 'active']);
        AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $team->id, 'status' => 'active', 'assigned_by' => $admin->id, 'assigned_at' => now()]);
    }

    $this->actingAs($admin)->get(route('admin.sports.bracket', $editionSport->sport))->assertOk()->assertSee('Tournament Bracket');
    $match = BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('round_number', 1)->where('match_number', 1)->firstOrFail();
    $winnerId = $match->team_one_id;

    $this->actingAs($admin)->post(route('admin.sports.bracket.result', [$editionSport->sport, $match]), ['winner_team_id' => $winnerId])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));

    $this->assertDatabaseHas('bracket_matches', ['id' => $match->id, 'winner_team_id' => $winnerId, 'status' => 'completed']);
    $this->assertDatabaseHas('bracket_matches', ['edition_sport_id' => $editionSport->id, 'round_number' => 2, 'match_number' => 1, 'team_one_id' => $winnerId]);

    $secondMatch = BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('round_number', 1)->where('match_number', 2)->firstOrFail();
    $this->actingAs($admin)->post(route('admin.sports.bracket.result', [$editionSport->sport, $secondMatch]), ['winner_team_id' => $secondMatch->team_one_id])
        ->assertRedirect(route('admin.sports.bracket', ['sport' => $editionSport->sport, 'edition_id' => $edition->id]));
    $this->assertDatabaseHas('bracket_matches', ['edition_sport_id' => $editionSport->id, 'round_number' => 2, 'match_number' => 1, 'team_one_id' => $winnerId, 'team_two_id' => $secondMatch->team_one_id, 'status' => 'pending', 'winner_team_id' => null]);
});

test('double elimination routes teams through losers bracket and creates a reset final', function () {
    $admin = bulkAssignmentAdmin(); $edition = bulkAssignmentEdition(); $editionSport = bulkAssignmentSport($edition, 'team', 'double_elimination');
    $teams = collect(range(1, 4))->map(fn ($number) => Team::query()->create(['edition_id'=>$edition->id,'name'=>'DOUBLE '.$number,'code'=>'DOUBLE'.$number,'status'=>'active']));
    foreach ($teams as $team) { $student = Student::factory()->create(['status'=>'active']); AthleteEntry::query()->create(['edition_sport_id'=>$editionSport->id,'student_id'=>$student->id,'team_id'=>$team->id,'status'=>'active','assigned_by'=>$admin->id,'assigned_at'=>now()]); }
    $this->actingAs($admin)->get(route('admin.sports.bracket', $editionSport->sport))->assertOk()->assertSee('Loser Bracket if needed');
    $play = function (string $bracket, int $round, int $winnerId, int $matchNumber = 1) use ($admin, $editionSport) { $match = BracketMatch::where('edition_sport_id',$editionSport->id)->where('bracket',$bracket)->where('round_number',$round)->where('match_number',$matchNumber)->firstOrFail(); $this->actingAs($admin)->post(route('admin.sports.bracket.result', [$editionSport->sport,$match]), ['winner_team_id'=>$winnerId])->assertRedirect(route('admin.sports.bracket', ['sport'=>$editionSport->sport, 'edition_id'=>$editionSport->edition_id])); };
    $play('winners', 1, $teams[0]->id, 1); $play('winners', 1, $teams[2]->id, 2);
    $play('losers', 1, $teams[1]->id); $play('winners', 2, $teams[0]->id);
    $play('losers', 2, $teams[1]->id); $play('finals', 1, $teams[1]->id);
    $this->assertDatabaseHas('bracket_matches', ['edition_sport_id'=>$editionSport->id,'bracket'=>'finals','round_number'=>2,'team_one_id'=>$teams[0]->id,'team_two_id'=>$teams[1]->id,'status'=>'pending']);
});
