<?php

use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\EligibilityService;
use Database\Seeders\DefaultIntramuralsSeeder;

test('default intramurals seeder adds the supplied roster without food committee only entries', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $team = Team::query()->where('edition_id', $edition->id)->where('code', 'trojan-warriors')->firstOrFail();

    expect(Student::query()->whereIn('student_number', [
        '2310042-2',
        '2310007-2',
        '2310060-1',
        '2310016-2',
        '2310067-2',
        '2310025-2',
        '2310072-1',
        '2310106-2',
        '2310005-2',
        '2310050-2',
        '2310036-2',
        '2310002-2',
        '2310099-1',
        '2310038-2',
        '2310045-2',
        '2310018-1',
        '2310047-2',
    ])->count())->toBe(17);

    foreach (['2310026-2', '2310028-2', '2310115-1', '2310008-2'] as $foodCommitteeStudentNumber) {
        $this->assertDatabaseMissing('students', ['student_number' => $foodCommitteeStudentNumber]);
    }

    $this->assertDatabaseHas('students', [
        'student_number' => '2310042-2',
        'first_name' => 'Jaylynne Gayle',
        'last_name' => 'Libodlibod',
        'year_level' => '4th',
        'section' => 'A',
    ]);

    expect(TeamMember::query()->where('team_id', $team->id)->count())->toBe(17);
    expect(AthleteEntry::query()->whereHas('student', fn ($query) => $query->where('student_number', '2310042-2'))->count())->toBe(0);
});

test('default intramurals roster maps student event names to configured sports and cultural events', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $assertEntry = function (string $studentNumber, string $sportCode, ?string $medicalStatus = null): void {
        $query = AthleteEntry::query()
            ->whereHas('student', fn ($studentQuery) => $studentQuery->where('student_number', $studentNumber))
            ->whereHas('editionSport.sport', fn ($sportQuery) => $sportQuery->where('code', $sportCode));

        if ($medicalStatus !== null) {
            $query->where('medical_certificate_status', $medicalStatus);
        }

        expect($query->exists())->toBeTrue();
    };

    $assertEntry('2310007-2', 'CULT-COLLAB-PAINT', 'not_required');
    $assertEntry('2310007-2', 'RUSSIAN-SOFTBALL', 'pending');
    $assertEntry('2310060-1', 'CHESS', 'not_required');
    $assertEntry('2310016-2', 'VOLLEYBALL', 'pending');
    $assertEntry('2310016-2', 'BEACH-VOLLEYBALL', 'pending');
    $assertEntry('2310005-2', 'BASKET-3X3', 'pending');
    $assertEntry('2310005-2', 'SOFTBALL', 'pending');
    $assertEntry('2310018-1', 'SHOT-PUT', 'pending');
    $assertEntry('2310018-1', 'DISCUS-THROW', 'pending');
    $assertEntry('2310018-1', 'JAVELIN-THROW', 'pending');
});

test('default Trojan Warriors roster follows participation rule exceptions', function () {
    $this->seed(DefaultIntramuralsSeeder::class);

    $edition = IntramuralEdition::query()->where('name', 'SLSUBC INTRAMURALS 2026')->firstOrFail();
    $eligibility = app(EligibilityService::class);
    $danilo = Student::query()->where('student_number', '2310018-1')->firstOrFail();
    $clarice = Student::query()->where('student_number', '2310005-2')->firstOrFail();

    $daniloEvaluation = $eligibility->evaluateStudent($danilo, $edition);
    $clariceEvaluation = $eligibility->evaluateStudent($clarice, $edition);

    expect($daniloEvaluation['possible_dq'])->toBeFalse()
        ->and($daniloEvaluation['counts'][EligibilityService::SLOT_INDIVIDUAL_DUAL])->toBe(3)
        ->and($clariceEvaluation['possible_dq'])->toBeFalse()
        ->and($clariceEvaluation['counts'][EligibilityService::SLOT_MAJOR])->toBe(1)
        ->and($clariceEvaluation['counts'][EligibilityService::SLOT_MINOR])->toBe(1);
});
