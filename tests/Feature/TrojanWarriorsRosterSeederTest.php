<?php

use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use Database\Seeders\DefaultIntramuralsSeeder;
use Database\Seeders\TrojanWarriorsRosterSeeder;

test('Trojan Warriors roster seeder imports the supplied sports and pair assignments', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $this->seed(TrojanWarriorsRosterSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $team = Team::query()->where('edition_id', $edition->id)->where('code', 'trojan-warriors')->firstOrFail();

    $this->assertDatabaseHas('students', [
        'first_name' => 'Jay-R',
        'last_name' => 'Danas',
        'gender' => 'Male',
    ]);
    $this->assertDatabaseHas('students', [
        'first_name' => 'Grace',
        'last_name' => 'Tago-on',
        'gender' => 'Female',
    ]);

    $romel = Student::query()->where('first_name', 'Romel')->where('last_name', 'Pitong')->firstOrFail();
    $earl = Student::query()->where('first_name', 'Earl Lorence')->where('last_name', 'Maraña')->firstOrFail();

    $pairKeys = AthleteEntry::query()
        ->whereIn('student_id', [$romel->id, $earl->id])
        ->whereHas('editionSport.sport', fn ($query) => $query->where('code', 'TABLE-TENNIS-DOUBLES'))
        ->pluck('pair_key')
        ->unique();

    expect($pairKeys)->toHaveCount(1)
        ->and($pairKeys->first())->toBe('trojan-tt-men-doubles-1');

    $jericho = Student::query()->where('first_name', 'Jericho')->where('last_name', 'Bisnar')->firstOrFail();
    $this->assertDatabaseHas('team_members', [
        'student_id' => $jericho->id,
        'team_id' => $team->id,
    ]);
    expect(AthleteEntry::query()
        ->where('student_id', $jericho->id)
        ->whereHas('editionSport.sport', fn ($query) => $query->whereIn('code', ['TRIPLE-JUMP', 'HIGH-JUMP', 'LONG-JUMP']))
        ->count())->toBe(3);
});

test('Trojan Warriors roster seeder is safe to rerun', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $this->seed(TrojanWarriorsRosterSeeder::class);

    $before = Student::query()->count();
    $beforeEntries = AthleteEntry::query()->count();

    $this->seed(TrojanWarriorsRosterSeeder::class);

    expect(Student::query()->count())->toBe($before)
        ->and(AthleteEntry::query()->count())->toBe($beforeEntries);
});
