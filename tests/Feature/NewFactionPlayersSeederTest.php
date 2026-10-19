<?php

use App\Models\AthleteEntry;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\Student;
use App\Models\TeamMember;
use Database\Seeders\DefaultIntramuralsSeeder;
use Database\Seeders\NewFactionPlayersSeeder;
use Illuminate\Support\Facades\DB;

function removeOctober19Players(): void
{
    $rows = collect(json_decode(file_get_contents(database_path('seeders/rosters/terraquatic_eagles.json')), true))
        ->where('source_snapshot_date', '2026-10-19');
    foreach (Student::all() as $student) {
        if ($rows->contains('name', $student->full_name)) {
            $student->athleteEntries()->delete();
            $student->teamMembers()->delete();
            $student->forceDelete();
        }
    }
}

test('additions import creates new players with merged sport codes and keeps cultural only members', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    removeOctober19Players();
    expect(Student::count())->toBe(531)->and(AthleteEntry::count())->toBe(523);

    $this->seed(NewFactionPlayersSeeder::class);

    expect(Student::count())->toBe(557)->and(TeamMember::count())->toBe(557)->and(AthleteEntry::count())->toBe(537);
    foreach ([
        'Michelle I. Muñez' => ['JAVELIN-THROW-WOMEN', 'VOLLEYBALL-WOMEN'],
        'Junemil Alaisa Solverez' => ['BASKET-5X5-WOMEN'],
        'Airon Jay V. Cabasisi' => ['BASEBALL', 'RUSSIAN-SOFTBALL'],
        'Jowie Goda' => ['TABLE-TENNIS-SINGLES-MEN'],
        'Erika Mae Magpatoc' => ['100M-RUN-WOMEN'],
        'Nico Gerona' => ['200M-RUN-MEN'],
        'Joshua Rebecca' => ['TRIPLE-JUMP-MEN'],
        'Dante A. Bihay Jr.' => [],
        'Alfred Beloy' => [],
    ] as $name => $codes) {
        $students = Student::all()->filter(fn (Student $student) => $student->full_name === $name);
        expect($students)->toHaveCount(1);
        $student = $students->first();
        expect($student->athleteEntries()->with('editionSport.sport')->get()->pluck('editionSport.sport.code')->sort()->values()->all())->toBe($codes);
        expect($student->teamMembers()->with('team')->firstOrFail()->team->code)->toBe('terraquatic-eagles');
        foreach ($student->athleteEntries()->with('editionSport.sport')->get() as $entry) {
            expect($entry->editionSport->sport->acceptsStudentGender($student->gender))->toBeTrue();
        }
    }
    $junemil = Student::where('first_name', 'Junemil')->firstOrFail();
    expect($junemil->gender)->toBe('Female')->and($junemil->year_level)->toBe('1st')->and($junemil->section)->toBe('A');
    $this->seed(NewFactionPlayersSeeder::class);
    expect(Student::count())->toBe(557)->and(AthleteEntry::count())->toBe(537);
});

test('additions import preserves edited and archived people and their scheduled and cultural registrations', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    removeOctober19Players();
    $student = Student::where('first_name', 'Hannah')->where('last_name', 'Parba')->firstOrFail();
    $student->update(['year_level' => '4th', 'section' => 'D']);
    $entry = $student->athleteEntries()->firstOrFail();
    $entry->update(['pair_key' => 'manual-pair', 'medical_certificate_status' => 'approved']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $entry->edition_sport_id, 'starts_at' => '2026-10-20 08:00:00', 'ends_at' => '2026-10-20 09:00:00', 'status' => 'scheduled']);
    $schedule->participants()->create(['athlete_entry_id' => $entry->id, 'slot' => '1', 'status' => 'active']);
    $cultural = EditionSport::whereHas('sport', fn ($query) => $query->where('code', 'CULT-SHORT-FILM'))->firstOrFail();
    AthleteEntry::create(['edition_sport_id' => $cultural->id, 'student_id' => $student->id, 'status' => 'active']);
    $known = Student::create(['student_number' => 'MANUALLY-ADDED-JUNEMIL', 'first_name' => 'Junemil', 'last_name' => 'Solverez', 'gender' => 'Female', 'school_year' => '2026-2027', 'year_level' => '2nd', 'section' => 'B', 'status' => 'active']);
    $archived = Student::where('first_name', 'Miles')->where('last_name', 'Lopez')->firstOrFail();
    $archived->update(['status' => 'archived']);
    $archived->delete();
    $studentIds = Student::withTrashed()->pluck('id');
    $entryIds = AthleteEntry::pluck('id');
    $beforeStudents = DB::table('students')->whereIn('id', $studentIds)->orderBy('id')->get()->toJson();
    $beforeEntries = DB::table('athlete_entries')->whereIn('id', $entryIds)->orderBy('id')->get()->toJson();

    $this->seed(NewFactionPlayersSeeder::class);

    expect(DB::table('students')->whereIn('id', $studentIds)->orderBy('id')->get()->toJson())->toBe($beforeStudents)
        ->and(DB::table('athlete_entries')->whereIn('id', $entryIds)->orderBy('id')->get()->toJson())->toBe($beforeEntries)
        ->and(Student::where('first_name', 'Junemil')->count())->toBe(1)
        ->and($known->fresh()->year_level)->toBe('2nd')
        ->and($known->athleteEntries()->count())->toBe(0)
        ->and($archived->fresh()->trashed())->toBeTrue();
    $this->assertDatabaseHas('schedule_participants', ['competition_schedule_id' => $schedule->id, 'athlete_entry_id' => $entry->id]);
    $before = DB::table('students')->orderBy('id')->get()->toJson();
    $this->seed(NewFactionPlayersSeeder::class);
    expect(DB::table('students')->orderBy('id')->get()->toJson())->toBe($before);
});

test('unavailable new sport rolls back all player additions', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    removeOctober19Players();
    EditionSport::whereHas('sport', fn ($query) => $query->where('code', 'TRIPLE-JUMP-MEN'))->update(['status' => 'archived']);

    expect(fn () => $this->seed(NewFactionPlayersSeeder::class))->toThrow(RuntimeException::class, 'TRIPLE-JUMP-MEN is unavailable');
    expect(Student::count())->toBe(531)->and(TeamMember::count())->toBe(531)->and(AthleteEntry::count())->toBe(523);
});

test('latest source adds only the newly listed registration to an existing player without replacing the roster', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $student = Student::where('student_number', 'TERRAQUATIC-EAGLES-JESSA-MAE-ARCA')->firstOrFail();
    $event = EditionSport::whereHas('sport', fn ($query) => $query->where('code', 'BASKET-3X3-WOMEN'))->firstOrFail();
    $student->athleteEntries()->where('edition_sport_id', $event->id)->delete();
    $student->update(['section' => 'D', 'year_level' => '4th']);
    $beforeStudents = DB::table('students')->orderBy('id')->get()->toJson();
    $entryIds = AthleteEntry::pluck('id');
    $beforeEntries = DB::table('athlete_entries')->whereIn('id', $entryIds)->orderBy('id')->get()->toJson();
    $this->seed(NewFactionPlayersSeeder::class);

    $entry = $student->athleteEntries()->where('edition_sport_id', $event->id)->firstOrFail();
    expect(DB::table('students')->orderBy('id')->get()->toJson())->toBe($beforeStudents)
        ->and(DB::table('athlete_entries')->whereIn('id', $entryIds)->orderBy('id')->get()->toJson())->toBe($beforeEntries)
        ->and(AthleteEntry::count())->toBe(537);
    $this->assertDatabaseHas('audit_logs', ['action' => 'roster.registration_added', 'auditable_id' => $entry->id]);
    $this->seed(NewFactionPlayersSeeder::class);
    expect(AthleteEntry::count())->toBe(537);

    $entry->update(['status' => 'withdrawn', 'medical_certificate_notes' => 'Keep manual edit']);
    $this->seed(NewFactionPlayersSeeder::class);
    expect($entry->fresh()->status)->toBe('withdrawn')->and($entry->fresh()->medical_certificate_notes)->toBe('Keep manual edit');
});
