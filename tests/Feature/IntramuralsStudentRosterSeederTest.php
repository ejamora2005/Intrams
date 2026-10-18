<?php

use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Student;
use Database\Seeders\DefaultIntramuralsSeeder;

test('default intramurals seeder uses the Trojan roster and removes the retired roster', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();

    $this->assertDatabaseHas('students', [
        'first_name' => 'Jay-R',
        'last_name' => 'Danas',
        'gender' => 'Male',
    ]);
    $this->assertDatabaseHas('students', [
        'first_name' => 'Grace',
        'last_name' => 'Gula',
        'gender' => 'Female',
    ]);
    $this->assertDatabaseHas('students', [
        'first_name' => 'Cristina',
        'last_name' => 'Tago-on',
        'gender' => 'Female',
    ]);

    foreach (['2310042-2', '2310007-2', '2310060-1', '2310018-1', '2310047-2'] as $legacyStudentNumber) {
        $this->assertDatabaseMissing('students', ['student_number' => $legacyStudentNumber]);
    }

    expect($edition->editionSports()->count())->toBeGreaterThan(0)
        ->and(AthleteEntry::query()->count())->toBeGreaterThan(0);
});

test('default Trojan roster includes the supplied sport mappings', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $assertEntry = function (string $firstName, string $lastName, string $sportCode): void {
        $student = Student::query()
            ->where('last_name', $lastName)
            ->get()
            ->first(fn (Student $candidate): bool => $candidate->full_name === $firstName.' '.$lastName);
        expect($student)->not->toBeNull();
        if ($student === null) {
            return;
        }

        $query = AthleteEntry::query()
            ->where('student_id', $student->id)
            ->whereHas('editionSport.sport', fn ($sportQuery) => $sportQuery->where('code', $sportCode));

        expect($query->exists())->toBeTrue();
    };

    $assertEntry('Jay-R', 'Danas', 'BASEBALL');
    $assertEntry('Anna Mae', 'Guzon', 'VOLLEYBALL-WOMEN');
    $assertEntry('Jericho', 'Bisnar', 'LONG-JUMP-MEN');
    $assertEntry('Grace Bilag', 'Gula', 'RUSSIAN-SOFTBALL');
    $assertEntry('Jhon Kristian', 'Cundangan', 'ESPORT-ML-MEN');
});

test('default Trojan roster seeding is safe to rerun', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $studentCount = Student::query()->count();
    $entryCount = AthleteEntry::query()->count();

    $this->seed(DefaultIntramuralsSeeder::class);

    expect(Student::query()->count())->toBe($studentCount)
        ->and(AthleteEntry::query()->count())->toBe($entryCount);
});
