<?php

use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\StandingsService;
use Database\Seeders\CurrentFactionRostersSeeder;
use Database\Seeders\DefaultIntramuralsSeeder;
use Database\Seeders\LegacyIntramuralsRosterCleanupSeeder;
use Database\Seeders\NewFactionPlayersSeeder;
use Database\Seeders\SeaDragonsRosterSeeder;
use Database\Seeders\TerraquaticEaglesRosterSeeder;
use Database\Seeders\TestIntramuralsDataSeeder;
use Database\Seeders\TrojanWarriorsRosterSeeder;
use Illuminate\Support\Facades\DB;

function productionRosterSnapshot(): array
{
    return collect([
        'students', 'team_members', 'athlete_entries', 'bracket_competitors',
        'bracket_matches', 'competition_schedules', 'schedule_participants',
        'sport_results', 'team_tallies', 'edition_sports', 'intramural_editions', 'teams',
    ])->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
}

test('production blocks every bundled faction import and legacy cleanup before changing any records', function () {
    Student::factory()->create(['student_number' => '2310042-2']);
    $before = productionRosterSnapshot();
    app()->instance('env', 'production');

    try {
        foreach ([
            CurrentFactionRostersSeeder::class,
            NewFactionPlayersSeeder::class,
            SeaDragonsRosterSeeder::class,
            TerraquaticEaglesRosterSeeder::class,
            TrojanWarriorsRosterSeeder::class,
            LegacyIntramuralsRosterCleanupSeeder::class,
        ] as $seeder) {
            $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])->assertSuccessful();
            expect(productionRosterSnapshot())->toBe($before);
        }
        app(SeaDragonsRosterSeeder::class)->run(newPlayersOnly: true);
        expect(productionRosterSnapshot())->toBe($before);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'roster.additions_imported']);
    } finally {
        app()->instance('env', 'testing');
    }
});

test('fresh production setup creates competition configuration without importing or deleting players', function () {
    Student::factory()->create(['student_number' => 'TEST-LIVE-PLAYER']);
    Student::factory()->create(['student_number' => '2310042-2']);
    $before = DB::table('students')->orderBy('id')->get()->toJson();
    app()->instance('env', 'production');

    try {
        $this->artisan('db:seed', ['--class' => DefaultIntramuralsSeeder::class, '--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => TestIntramuralsDataSeeder::class, '--force' => true])->assertSuccessful();

        expect(DB::table('students')->orderBy('id')->get()->toJson())->toBe($before)
            ->and(IntramuralEdition::count())->toBe(1)
            ->and(Team::count())->toBe(3)
            ->and(TeamMember::count())->toBe(0)
            ->and(AthleteEntry::count())->toBe(0)
            ->and(IntramuralEdition::firstOrFail()->editionSports()->count())->toBeGreaterThan(0);

        $configured = productionRosterSnapshot();
        $this->artisan('db:seed', ['--class' => DefaultIntramuralsSeeder::class, '--force' => true])->assertSuccessful();
        expect(productionRosterSnapshot())->toBe($configured);
    } finally {
        app()->instance('env', 'testing');
    }
});

test('repeated production deployment setup preserves live edits removed source names registrations and scores', function () {
    $this->seed(DefaultIntramuralsSeeder::class);
    $removed = Student::where('first_name', 'Jay-R')->where('last_name', 'Danas')->firstOrFail();
    $removedNumber = $removed->student_number;
    $removed->athleteEntries()->delete();
    $removed->teamMembers()->delete();
    $removed->forceDelete();
    $edited = Student::where('first_name', 'Grace')->where('last_name', 'Gula')->firstOrFail();
    $edited->update(['first_name' => 'Updated live name', 'section' => 'LIVE']);
    $edited->athleteEntries()->update(['status' => 'withdrawn']);
    Student::factory()->create(['student_number' => 'TEST-LIVE-PLAYER']);
    $edition = IntramuralEdition::firstOrFail();
    $edition->update(['name' => 'Live competition name']);
    $event = $edition->editionSports()->whereHas('sport', fn ($query) => $query->where('code', 'BASKET-3X3-MEN'))->firstOrFail();
    $teams = Team::where('edition_id', $edition->id)->orderBy('id')->pluck('id');
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0], 2 => $teams[1], 3 => $teams[2]], User::factory()->create(['role' => 'admin']));
    $before = productionRosterSnapshot();
    app()->instance('env', 'production');

    try {
        for ($deployment = 0; $deployment < 2; $deployment++) {
            foreach ([DefaultIntramuralsSeeder::class, CurrentFactionRostersSeeder::class, NewFactionPlayersSeeder::class] as $seeder) {
                $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])->assertSuccessful();
            }
        }

        expect(productionRosterSnapshot())->toBe($before);
        $this->assertDatabaseMissing('students', ['student_number' => $removedNumber]);
    } finally {
        app()->instance('env', 'testing');
    }
});
