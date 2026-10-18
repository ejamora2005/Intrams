<?php

use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\Student;
use App\Models\TeamMember;
use Database\Seeders\CurrentFactionRostersSeeder;
use Database\Seeders\DefaultIntramuralsSeeder;

test('current source snapshot imports one member per name and every exact gender matched sports registration', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $members = TeamMember::with(['student.athleteEntries.editionSport.sport', 'team'])->get();
    expect($members)->toHaveCount(531)->and(AthleteEntry::count())->toBe(522);
    foreach (['sea_dragons' => 'sea-dragons', 'terraquatic_eagles' => 'terraquatic-eagles', 'trojan_warriors' => 'trojan-warriors'] as $file => $teamCode) {
        foreach (json_decode(file_get_contents(database_path('seeders/rosters/'.$file.'.json')), true) as $row) {
            $member = $members->first(fn ($member) => $member->team->code === $teamCode && $member->student->full_name === $row['name']);
            expect($member)->not->toBeNull();
            $entries = $member->student->athleteEntries;
            expect($entries->pluck('editionSport.sport.code')->sort()->values()->all())->toBe(collect($row['sport_codes'])->sort()->values()->all());
            foreach ($entries as $entry) {
                expect($entry->editionSport->sport->acceptsStudentGender($member->student->gender))->toBeTrue()
                    ->and($entry->editionSport->sport->status)->toBe('active');
            }
        }
    }
    $ailicka = $members->first(fn ($member) => $member->student->full_name === 'Ailicka Cojano')->student;
    expect($ailicka->section)->toBe('A')->and($ailicka->year_level)->toBe('3rd')
        ->and($ailicka->athleteEntries->pluck('editionSport.sport.code')->sort()->values()->all())->toBe(['BASKET-3X3-WOMEN', 'RUSSIAN-SOFTBALL']);
    expect($members->filter(fn ($member) => $member->student->full_name === 'Kris Tindugan'))->toHaveCount(1);
});

test('roster refresh merges verified name aliases and preserves existing cultural registrations', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $canonical = Student::where('first_name', 'Miles')->where('last_name', 'Lopez')->firstOrFail();
    $membership = $canonical->teamMembers()->firstOrFail();
    $duplicate = Student::create(['student_number' => 'OLD-MILED-LOPEZ', 'first_name' => 'Miled', 'last_name' => 'Lopez', 'gender' => 'Male', 'school_year' => '2026-2027', 'year_level' => '4th', 'section' => 'A', 'status' => 'active']);
    TeamMember::create(['edition_id' => $membership->edition_id, 'team_id' => $membership->team_id, 'student_id' => $duplicate->id]);
    $baseball = $canonical->athleteEntries()->firstOrFail();
    $duplicateBaseball = AthleteEntry::create(['edition_sport_id' => $baseball->edition_sport_id, 'student_id' => $duplicate->id, 'team_id' => $membership->team_id, 'status' => 'active']);
    $competitor = BracketCompetitor::create(['edition_sport_id' => $baseball->edition_sport_id, 'type' => 'team', 'identity_key' => 'team:'.$membership->team_id, 'team_id' => $membership->team_id, 'label' => 'Trojan Warriors', 'athlete_entry_ids' => [$baseball->id, $duplicateBaseball->id]]);
    $photography = EditionSport::where('edition_id', $membership->edition_id)->whereHas('sport', fn ($query) => $query->where('code', 'CULT-DIGI-PHOTO'))->firstOrFail();
    $cultural = AthleteEntry::create(['edition_sport_id' => $photography->id, 'student_id' => $duplicate->id, 'team_id' => $membership->team_id, 'status' => 'active']);
    $joel = Student::where('first_name', 'Joel')->where('last_name', 'Portugaliza')->firstOrFail();
    $joel->update(['middle_name' => null]);
    $badminton = AthleteEntry::whereHas('student', fn ($query) => $query->where('first_name', 'Kris')->where('last_name', 'Tindugan'))
        ->whereHas('editionSport.sport', fn ($query) => $query->where('code', 'BADMINTON-DOUBLES-WOMEN'))->firstOrFail();
    $badminton->update(['pair_key' => 'existing-badminton-pair']);

    $this->seed(CurrentFactionRostersSeeder::class);

    expect($duplicate->fresh()->trashed())->toBeTrue()->and($cultural->fresh()->student_id)->toBe($canonical->id)
        ->and($joel->fresh()->full_name)->toBe('Joel Capote Portugaliza')
        ->and($competitor->fresh()->athlete_entry_ids)->toBe([$baseball->id])
        ->and($badminton->fresh()->pair_key)->toBe('existing-badminton-pair')
        ->and(Student::count())->toBe(531)->and(TeamMember::count())->toBe(531)->and(AthleteEntry::count())->toBe(523);
    $this->seed(CurrentFactionRostersSeeder::class);
    expect(Student::count())->toBe(531)->and(TeamMember::count())->toBe(531)->and(AthleteEntry::count())->toBe(523);
});

test('cultural only members outside the sports source retain their membership and registrations', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $member = TeamMember::firstOrFail();
    $student = Student::create(['student_number' => 'KEEP-CULTURE-MEMBER', 'first_name' => 'Existing', 'last_name' => 'Cultural Member', 'gender' => 'Female', 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    $membership = TeamMember::create(['edition_id' => $member->edition_id, 'team_id' => $member->team_id, 'student_id' => $student->id]);
    $event = EditionSport::where('edition_id', $member->edition_id)->whereHas('sport', fn ($query) => $query->where('code', 'CULT-SHORT-FILM'))->firstOrFail();
    $entry = AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $member->team_id, 'status' => 'active']);
    $this->seed(CurrentFactionRostersSeeder::class);
    $this->assertDatabaseHas('team_members', ['id' => $membership->id]);
    $this->assertDatabaseHas('athlete_entries', ['id' => $entry->id, 'status' => 'active']);
});

test('a scheduled obsolete registration rolls back changes to every faction', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $seaStudent = Student::where('first_name', 'Christian')->where('middle_name', 'Jay')->firstOrFail();
    $seaStudent->update(['year_level' => '4th']);
    $member = TeamMember::whereHas('team', fn ($query) => $query->where('code', 'trojan-warriors'))->firstOrFail();
    $obsolete = Student::create(['student_number' => 'SCHEDULED-OLD-ROSTER', 'first_name' => 'Old', 'last_name' => 'Scheduled Athlete', 'gender' => 'Male', 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    TeamMember::create(['edition_id' => $member->edition_id, 'team_id' => $member->team_id, 'student_id' => $obsolete->id]);
    $event = EditionSport::where('edition_id', $member->edition_id)->whereHas('sport', fn ($query) => $query->where('code', '100M-RUN-MEN'))->firstOrFail();
    $entry = AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $obsolete->id, 'status' => 'active']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 10:00:00', 'status' => 'scheduled']);
    $schedule->participants()->create(['athlete_entry_id' => $entry->id, 'slot' => '1', 'status' => 'active']);
    expect(fn () => app(CurrentFactionRostersSeeder::class)->run())->toThrow(RuntimeException::class, 'used in schedules');
    expect($seaStudent->fresh()->year_level)->toBe('4th')->and(TeamMember::count())->toBe(532);
    $this->assertDatabaseHas('athlete_entries', ['id' => $entry->id]);
});

test('refreshed source adds the new chess and relay participants and keeps cultural only names', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    foreach ([
        'Judea Loui M. Marfil' => ['CHESS-WOMEN'],
        'April Joy M. Marfil' => ['CHESS-WOMEN'],
        'Hannah Ajesha Parba' => ['RELAY-4X100-WOMEN'],
        'Analy Sermillas' => ['JAVELIN-THROW-WOMEN', 'RELAY-4X100-WOMEN'],
        'Alyka Florida Palen' => [],
    ] as $name => $codes) {
        $student = Student::all()->first(fn (Student $student) => $student->full_name === $name);
        expect($student)->not->toBeNull();
        expect($student->athleteEntries()->with('editionSport.sport')->get()->pluck('editionSport.sport.code')->sort()->values()->all())->toBe($codes);
        expect($student->teamMembers()->count())->toBe(1);
    }
});

test('expanded Trojan names retain student ids and scheduled registrations on refresh', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $students = [];
    foreach ([
        'Christine Belinario Alvero' => ['Christine', 'Alvero'],
        'Danicha Jane Cabudbud Gomez' => ['Danicha', 'Gomez'],
        'Kristine Jamero Lapasanda' => ['Kristine', 'Lapasanda'],
    ] as $canonical => [$firstName, $lastName]) {
        $student = Student::all()->first(fn (Student $student) => $student->full_name === $canonical);
        $students[$canonical] = ['id' => $student->id, 'entries' => $student->athleteEntries()->pluck('id')->all()];
        $student->update(['first_name' => $firstName, 'middle_name' => null, 'last_name' => $lastName]);
    }
    $entry = AthleteEntry::findOrFail($students['Christine Belinario Alvero']['entries'][0]);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $entry->edition_sport_id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 10:00:00', 'status' => 'scheduled']);
    $schedule->participants()->create(['athlete_entry_id' => $entry->id, 'slot' => '1', 'status' => 'active']);
    $this->seed(CurrentFactionRostersSeeder::class);
    foreach ($students as $canonical => $previous) {
        $student = Student::findOrFail($previous['id']);
        expect($student->full_name)->toBe($canonical)
            ->and($student->athleteEntries()->pluck('id')->all())->toBe($previous['entries']);
    }
    expect(Student::count())->toBe(531);
    $this->assertDatabaseHas('schedule_participants', ['competition_schedule_id' => $schedule->id, 'athlete_entry_id' => $entry->id]);
});
