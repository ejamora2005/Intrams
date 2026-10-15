<?php

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use Database\Seeders\DefaultIntramuralsSeeder;
use Database\Seeders\TrojanWarriorsRosterSeeder;

test('default roster seeders import the three current team sheets', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $trojan = Team::query()->where('edition_id', $edition->id)->where('code', 'trojan-warriors')->firstOrFail();
    $seaDragons = Team::query()->where('edition_id', $edition->id)->where('code', 'sea-dragons')->firstOrFail();
    $eagles = Team::query()->where('edition_id', $edition->id)->where('code', 'terraquatic-eagles')->firstOrFail();

    expect(TeamMember::query()->where('team_id', $trojan->id)->count())->toBe(109)
        ->and(TeamMember::query()->where('team_id', $seaDragons->id)->count())->toBe(124)
        ->and(TeamMember::query()->where('team_id', $eagles->id)->count())->toBe(43);

    $graceGula = Student::query()->where('first_name', 'Grace')->where('last_name', 'Gula')->firstOrFail();
    $cristinaTagoon = Student::query()->where('first_name', 'Cristina')->where('last_name', 'Tago-on')->firstOrFail();
    expect($graceGula->gender)->toBe('Female')
        ->and($cristinaTagoon->gender)->toBe('Female')
        ->and($graceGula->year_level)->toBe('4th')
        ->and($graceGula->section)->toBe('A');
    $this->assertDatabaseHas('team_members', ['team_id' => $trojan->id, 'student_id' => $graceGula->id]);
    $this->assertDatabaseHas('team_members', ['team_id' => $trojan->id, 'student_id' => $cristinaTagoon->id]);

    $seaStudent = Student::query()->where('last_name', 'Tindugan')->firstOrFail();
    expect($seaStudent->year_level)->toBe('3rd')
        ->and($seaStudent->section)->toBe('A');
    expect(AthleteEntry::query()
        ->where('student_id', $seaStudent->id)
        ->whereHas('editionSport.sport', fn ($query) => $query->whereIn('code', ['ESPORT-ML-MEN', 'ESPORT-ML-WOMEN', 'BADMINTON-DOUBLES-MEN', 'BADMINTON-DOUBLES-WOMEN']))
        ->count())->toBe(2);

    $modifiedVolleyball = EditionSport::query()
        ->where('edition_id', $edition->id)
        ->whereHas('sport', fn ($query) => $query->where('code', 'MODIFIED-VOLLEYBALL-WOMEN'))
        ->firstOrFail();
    expect($modifiedVolleyball->participant_type)->toBe('team');

    $modifiedVolleyballStudent = Student::query()->where('last_name', 'Rafalle')->firstOrFail();
    $this->assertDatabaseHas('athlete_entries', [
        'edition_sport_id' => $modifiedVolleyball->id,
        'student_id' => $modifiedVolleyballStudent->id,
        'team_id' => $eagles->id,
    ]);

    $eagleStudent = Student::query()->where('last_name', 'Gantala')->firstOrFail();
    expect($eagleStudent->first_name)->toBe('Antonette')
        ->and($eagleStudent->middle_name)->toBe('L.')
        ->and($eagleStudent->year_level)->toBe('3rd')
        ->and($eagleStudent->section)->toBe('A');

    $incompleteSectionStudent = Student::query()->where('last_name', 'Traboc')->firstOrFail();
    expect($incompleteSectionStudent->middle_name)->toBe('S.')
        ->and($incompleteSectionStudent->year_level)->toBe('3rd')
        ->and($incompleteSectionStudent->section)->toBeNull();

    $festivalDance = EditionSport::query()
        ->where('edition_id', $edition->id)
        ->whereHas('sport', fn ($query) => $query->where('code', 'CULT-FEST-DANCE'))
        ->firstOrFail();
    $festivalStudent = Student::query()->where('last_name', 'Caadyang')->firstOrFail();
    $this->assertDatabaseHas('athlete_entries', [
        'edition_sport_id' => $festivalDance->id,
        'student_id' => $festivalStudent->id,
    ]);
});

test('Trojan roster seeder preserves pair assignments and is safe to rerun', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $romel = Student::query()->where('first_name', 'Romel')->where('last_name', 'Pitong')->firstOrFail();
    $earl = Student::query()->where('first_name', 'Earl')->where('middle_name', 'Lorence')->where('last_name', 'Maraña')->firstOrFail();
    $pairKeys = AthleteEntry::query()
        ->whereIn('student_id', [$romel->id, $earl->id])
        ->whereHas('editionSport.sport', fn ($query) => $query->where('code', 'TABLE-TENNIS-DOUBLES-MEN'))
        ->pluck('pair_key')
        ->unique();
    expect($pairKeys)->toHaveCount(1)
        ->and($pairKeys->first())->toBe('trojan-tt-men-doubles-1');

    $before = [
        'students' => Student::query()->count(),
        'memberships' => TeamMember::query()->count(),
        'entries' => AthleteEntry::query()->count(),
    ];

    $this->seed(TrojanWarriorsRosterSeeder::class);

    expect(Student::query()->count())->toBe($before['students'])
        ->and(TeamMember::query()->count())->toBe($before['memberships'])
        ->and(AthleteEntry::query()->count())->toBe($before['entries']);
});

test('Trojan roster seeder removes obsolete members and sport registrations', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $team = Team::query()->where('edition_id', $edition->id)->where('code', 'trojan-warriors')->firstOrFail();
    $obsoleteStudent = Student::query()->create([
        'student_number' => 'OLD-TROJAN-ROSTER-RECORD',
        'first_name' => 'Old',
        'last_name' => 'Roster Student',
        'school_year' => $edition->school_year,
        'course_id' => $team->course_id,
        'gender' => 'Male',
        'year_level' => '1st',
        'section' => 'A',
        'status' => 'active',
    ]);
    $membership = TeamMember::query()->create([
        'edition_id' => $edition->id,
        'team_id' => $team->id,
        'student_id' => $obsoleteStudent->id,
        'assigned_at' => now(),
    ]);
    $baseball = EditionSport::query()
        ->where('edition_id', $edition->id)
        ->whereHas('sport', fn ($query) => $query->where('code', 'BASEBALL'))
        ->firstOrFail();
    $obsoleteEntry = AthleteEntry::query()->create([
        'edition_sport_id' => $baseball->id,
        'student_id' => $obsoleteStudent->id,
        'team_id' => $team->id,
        'status' => 'active',
        'assigned_at' => now(),
    ]);

    $this->seed(TrojanWarriorsRosterSeeder::class);

    $this->assertDatabaseMissing('team_members', ['id' => $membership->id]);
    $this->assertDatabaseMissing('athlete_entries', ['id' => $obsoleteEntry->id]);
    expect(Student::query()->whereKey($obsoleteStudent->id)->exists())->toBeTrue();
});
