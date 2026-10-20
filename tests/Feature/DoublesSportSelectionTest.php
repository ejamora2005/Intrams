<?php

use App\Models\AthleteEntry;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function doublesSelectionScenario(string $role, string $gender, string $sportCode): array
{
    $edition = IntramuralEdition::create(['name' => 'Doubles editing', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $course = Course::create(['code' => 'DBL', 'name' => 'Doubles test course', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'course_id' => $course->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $user = User::factory()->create(['role' => $role, 'status' => 'active', 'managed_team_id' => $role === 'gam' ? $team->id : null]);
    $student = Student::factory()->create(['gender' => $gender, 'course_id' => $course->id, 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
    $definition = collect(config('intramurals.default_competitions'))->firstWhere('code', $sportCode);
    $sport = Sport::create(['code' => $sportCode, 'name' => $definition['name'], 'participant_category' => $definition['participant_category'], 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'dual', 'game_mechanic' => 'round_robin', 'scoring_rules' => $definition['scoring_rules'], 'status' => 'active']);

    return [$edition, $team, $user, $student, $event];
}

test('staff can retain remove and reselect doubles without changing the other partner', function (string $role, string $gender, string $code) {
    [$edition, $team, $user, $student, $event] = doublesSelectionScenario($role, $gender, $code);
    $partner = Student::factory()->create(['gender' => $gender, 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $partner->id]);
    $entry = AthleteEntry::create(['student_id' => $student->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'pair_key' => 'existing-doubles-pair', 'status' => 'active', 'medical_certificate_status' => 'verified', 'medical_certificate_reviewed_by' => $user->id, 'medical_certificate_reviewed_at' => now(), 'medical_certificate_notes' => 'Keep this review']);
    $partnerEntry = AthleteEntry::create(['student_id' => $partner->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'pair_key' => $entry->pair_key, 'status' => 'active', 'medical_certificate_status' => 'verified']);
    $ownBefore = $entry->fresh()->getAttributes();
    $partnerBefore = $partnerEntry->fresh()->getAttributes();
    $runSport = Sport::create(['code' => '100M-RUN-'.($gender === 'Male' ? 'MEN' : 'WOMEN'), 'name' => '100m run', 'participant_category' => strtolower($gender), 'status' => 'active']);
    $run = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $runSport->id, 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => ['point_system' => 'sports_athletics'], 'status' => 'active']);
    $save = function (array $ids) use ($role, $team, $user, $student): void {
        $payload = ['edition_sport_ids' => $ids];
        $url = route('gam.teams.players.sports.update', [$team, $student]);
        if ($role === 'admin') {
            $payload += $student->only(['first_name', 'middle_name', 'last_name', 'school_year', 'course_id', 'gender', 'year_level', 'section', 'status']);
            $url = route('admin.students.update', $student);
        }
        $this->actingAs($user)->put($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
    };

    $save([$event->id]);
    expect($entry->fresh()->getAttributes())->toBe($ownBefore);
    $save([$run->id]);
    expect($entry->fresh()->status)->toBe('inactive')->and($entry->fresh()->pair_key)->toBe('existing-doubles-pair');
    $save([$event->id]);
    expect($entry->fresh()->status)->toBe('active')
        ->and($entry->fresh()->pair_key)->toBe('existing-doubles-pair')
        ->and($partnerEntry->fresh()->getAttributes())->toBe($partnerBefore)
        ->and(AthleteEntry::where('student_id', $student->id)->where('edition_sport_id', $event->id)->count())->toBe(1)
        ->and($student->fresh()->student_number)->toBe($student->student_number);
    $this->assertDatabaseHas('athlete_entries', ['student_id' => $student->id, 'edition_sport_id' => $run->id, 'status' => 'inactive']);
})->with([
    ['admin', 'Male', 'BADMINTON-DOUBLES-MEN'], ['admin', 'Female', 'BADMINTON-DOUBLES-WOMEN'],
    ['admin', 'Male', 'TABLE-TENNIS-DOUBLES-MEN'], ['admin', 'Female', 'TABLE-TENNIS-DOUBLES-WOMEN'],
    ['gam', 'Male', 'BADMINTON-DOUBLES-MEN'], ['gam', 'Female', 'BADMINTON-DOUBLES-WOMEN'],
    ['gam', 'Male', 'TABLE-TENNIS-DOUBLES-MEN'], ['gam', 'Female', 'TABLE-TENNIS-DOUBLES-WOMEN'],
]);

test('staff can add a new player with doubles selected before assigning a partner', function (string $role, string $gender, string $code) {
    [, $team, $user, , $event] = doublesSelectionScenario($role, $gender, $code);
    $payload = ['first_name' => 'New doubles', 'last_name' => 'Player', 'gender' => $gender, 'course_id' => $team->course_id, 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'B', 'status' => 'active', 'edition_sport_ids' => [$event->id]];
    $url = $role === 'admin' ? route('admin.students.store') : route('gam.teams.players.store', $team);
    $this->actingAs($user)->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
    $student = Student::where('first_name', 'New doubles')->firstOrFail();
    $entry = $student->athleteEntries()->sole();
    expect($entry->edition_sport_id)->toBe($event->id)->and($entry->team_id)->toBe($team->id)
        ->and($entry->status)->toBe('active')->and($entry->pair_key)->toBeNull();
})->with([
    ['admin', 'Male', 'BADMINTON-DOUBLES-MEN'], ['admin', 'Female', 'TABLE-TENNIS-DOUBLES-WOMEN'],
    ['gam', 'Male', 'TABLE-TENNIS-DOUBLES-MEN'], ['gam', 'Female', 'BADMINTON-DOUBLES-WOMEN'],
]);

test('doubles editing keeps gender and participation validation with no partial changes', function () {
    [$edition, $team, $user, $student, $event] = doublesSelectionScenario('admin', 'Male', 'BADMINTON-DOUBLES-MEN');
    $entry = AthleteEntry::create(['student_id' => $student->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'status' => 'active']);
    $sport = Sport::create(['code' => 'TABLE-TENNIS-DOUBLES-WOMEN', 'name' => 'Table Tennis (Doubles - Women)', 'participant_category' => 'female', 'status' => 'active']);
    $wrongGender = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'dual', 'game_mechanic' => 'round_robin', 'scoring_rules' => ['point_system' => 'sports_minor'], 'status' => 'active']);
    $before = $entry->fresh()->getAttributes();
    $profile = $student->only(['first_name', 'middle_name', 'last_name', 'school_year', 'course_id', 'gender', 'year_level', 'section', 'status']);
    $this->actingAs($user)->put(route('admin.students.update', $student), [...$profile, 'first_name' => 'Rejected', 'edition_sport_ids' => [$wrongGender->id]])->assertSessionHasErrors('edition_sport_ids');
    expect($student->fresh()->first_name)->toBe($profile['first_name'])->and($entry->fresh()->getAttributes())->toBe($before);

    $sport->update(['participant_category' => 'male']);
    $this->actingAs($user)->put(route('admin.students.update', $student), [...$profile, 'edition_sport_ids' => [$event->id, $wrongGender->id]])->assertSessionHasErrors('edition_sport_ids');
    expect($entry->fresh()->getAttributes())->toBe($before)->and(AthleteEntry::count())->toBe(1);
});

test('registered doubles players can be paired using their existing rows and medical reviews', function () {
    [$edition, $team, $user, $student, $event] = doublesSelectionScenario('admin', 'Female', 'TABLE-TENNIS-DOUBLES-WOMEN');
    $partner = Student::factory()->create(['gender' => 'Female', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $partner->id]);
    $entry = AthleteEntry::create(['student_id' => $student->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'status' => 'active', 'medical_certificate_status' => 'verified', 'medical_certificate_notes' => 'Reviewed before pairing']);
    $partnerEntry = AthleteEntry::create(['student_id' => $partner->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'status' => 'inactive', 'pair_key' => 'old-pair']);
    $response = $this->actingAs($user)->get(route('admin.sports.participants.assign', ['sport' => $event->sport, 'edition_id' => $edition->id, 'team_id' => $team->id]))->assertOk();
    expect($response->viewData('students')->pluck('id')->all())->toContain($student->id, $partner->id);
    $this->actingAs($user)->post(route('admin.sports.participants.store', $event->sport), ['edition_id' => $edition->id, 'team_id' => $team->id, 'student_ids' => [$student->id, $partner->id]])->assertSessionHasNoErrors()->assertRedirect();
    expect($entry->fresh()->pair_key)->not->toBeNull()
        ->and($entry->fresh()->pair_key)->toBe($partnerEntry->fresh()->pair_key)
        ->and($partnerEntry->fresh()->status)->toBe('active')
        ->and($entry->fresh()->medical_certificate_status)->toBe('verified')
        ->and($entry->fresh()->medical_certificate_notes)->toBe('Reviewed before pairing')
        ->and(AthleteEntry::count())->toBe(2);
});

test('pair assignment includes an orphaned partner but cannot take a player from a complete pair', function () {
    [$edition, $team, $user, $student, $event] = doublesSelectionScenario('admin', 'Male', 'BADMINTON-DOUBLES-MEN');
    $players = Student::factory()->count(3)->create(['gender' => 'Male', 'status' => 'active']);
    foreach ($players as $index => $player) {
        TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $player->id]);
        AthleteEntry::create(['student_id' => $player->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'pair_key' => $index < 2 ? 'complete-pair' : 'orphan-pair', 'status' => 'active']);
    }
    $response = $this->actingAs($user)->get(route('admin.sports.participants.assign', ['sport' => $event->sport, 'edition_id' => $edition->id, 'team_id' => $team->id]))->assertOk();
    expect($response->viewData('students')->pluck('id')->all())->toContain($student->id, $players[2]->id)->not->toContain($players[0]->id, $players[1]->id);
    $before = DB::table('athlete_entries')->orderBy('id')->get()->toJson();
    $this->actingAs($user)->post(route('admin.sports.participants.store', $event->sport), ['edition_id' => $edition->id, 'team_id' => $team->id, 'student_ids' => [$student->id, $players[0]->id]])->assertSessionHasErrors('student_ids');
    expect(DB::table('athlete_entries')->orderBy('id')->get()->toJson())->toBe($before);
});
