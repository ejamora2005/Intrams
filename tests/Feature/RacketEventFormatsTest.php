<?php

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\AthleteEntryService;
use App\Services\BracketService;
use App\Services\RacketEventFormatService;
use Database\Seeders\DefaultSportsSeeder;
use Illuminate\Support\Facades\DB;

function racketFormatEdition(): IntramuralEdition
{
    return IntramuralEdition::create(['name' => 'Racket formats', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
}

function racketFormatEvent(IntramuralEdition $edition, string $code): EditionSport
{
    return $edition->editionSports()->whereHas('sport', fn ($query) => $query->where('code', $code))->firstOrFail();
}

test('current chess is team only and badminton keeps singles doubles and team for each gender', function () {
    $edition = racketFormatEdition();
    $this->seed(DefaultSportsSeeder::class);
    foreach (['MEN', 'WOMEN'] as $gender) {
        foreach (['CHESS' => 'team', 'BADMINTON-SINGLES' => 'individual', 'BADMINTON-DOUBLES' => 'dual', 'BADMINTON-TEAM' => 'team'] as $code => $type) {
            $event = racketFormatEvent($edition, $code.'-'.$gender);
            expect($event->participant_type)->toBe($type)->and($event->sport->status)->toBe('active')
                ->and($event->sport->catalogue_group_key)->toBe($type === 'team' ? 'team' : 'individual');
        }
    }
    $options = app(AthleteEntryService::class)->selectableSportsFor($edition, true);
    expect($options->filter(fn ($event) => str_starts_with($event->sport->code, 'BADMINTON-')))->toHaveCount(6);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $student = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
    foreach ([route('admin.students.create'), route('admin.students.edit', $student)] as $url) {
        $response = $this->actingAs($admin)->get($url)->assertOk();
        foreach (['Team', 'Singles', 'Doubles'] as $format) {
            $response->assertSee('Badminton ('.$format.' - Men)')->assertSee('Badminton ('.$format.' - Women)');
        }
        $response->assertSee('Assign both partners from sport management');
    }
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $this->actingAs($tabulator)->get(route('tabulator.dashboard'))->assertOk()->assertSee('Chess (Men)')->assertSee('Badminton (Team - Men)');
});

test('format repair preserves badminton registrations and recorded competition data', function () {
    $edition = racketFormatEdition();
    app(RacketEventFormatService::class)->sync();
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $chess = racketFormatEvent($edition, 'CHESS-MEN');
    $chess->update(['participant_type' => 'individual', 'game_mechanic' => 'single_elimination', 'scoring_rules' => ['placements' => ['1' => 37]]]);
    $chessPlayer = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $chessPlayer->id]);
    $chessEntry = AthleteEntry::create(['edition_sport_id' => $chess->id, 'student_id' => $chessPlayer->id, 'status' => 'active', 'medical_certificate_status' => 'not_required']);
    $pair = [];
    foreach (['BADMINTON-SINGLES-MEN', 'BADMINTON-DOUBLES-MEN'] as $code) {
        $event = racketFormatEvent($edition, $code);
        foreach (range(1, $event->participant_type === 'dual' ? 2 : 1) as $number) {
            $student = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
            $pair[] = AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $team->id, 'pair_key' => $event->participant_type === 'dual' ? 'retained-pair' : null, 'status' => 'active'])->fresh()->getAttributes();
        }
    }
    $badmintonTeam = racketFormatEvent($edition, 'BADMINTON-TEAM-WOMEN');
    $badmintonTeam->update(['participant_type' => 'dual']);
    SportResult::create(['edition_sport_id' => $chess->id, 'placements_json' => ['1' => $team->id], 'points_json' => [$team->id => 37], 'submitted_at' => now()]);
    TeamTally::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'gold_count' => 1, 'points' => 37, 'updated_at' => now()]);
    BracketMatch::create(['edition_sport_id' => $chess->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'team_one_id' => $team->id, 'winner_team_id' => $team->id, 'status' => 'completed']);
    CompetitionSchedule::create(['edition_sport_id' => $chess->id, 'starts_at' => '2026-10-20 11:00:00', 'ends_at' => '2026-10-20 13:00:00', 'status' => 'scheduled']);
    $tables = ['sport_results', 'team_tallies', 'bracket_matches', 'competition_schedules', 'students', 'team_members'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()]);
    $migration = require database_path('migrations/2026_10_20_030000_correct_chess_and_badminton_event_formats.php');
    $migration->up();
    expect($chess->fresh()->participant_type)->toBe('team')
        ->and($chess->fresh()->game_mechanic)->toBe('single_elimination')
        ->and($chess->fresh()->scoring_rules)->toBe(['placements' => ['1' => 37]])
        ->and($chessEntry->fresh()->team_id)->toBe($team->id)
        ->and($chessEntry->fresh()->medical_certificate_status)->toBe('not_required')
        ->and($badmintonTeam->fresh()->participant_type)->toBe('team');
    foreach ($pair as $entry) {
        expect(AthleteEntry::findOrFail($entry['id'])->getAttributes())->toBe($entry);
    }
    foreach ($before as $table => $json) {
        expect(DB::table($table)->orderBy('id')->get()->toJson())->toBe($json);
    }
    $auditCount = AuditLog::count();
    $migration->up();
    expect(AuditLog::count())->toBe($auditCount)->and(AthleteEntry::count())->toBe(4);
});

test('admin and GAM can save chess and badminton team for the matching gender', function (string $gender, string $suffix) {
    $edition = racketFormatEdition();
    app(RacketEventFormatService::class)->sync();
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $student = Student::factory()->create(['gender' => $gender, 'school_year' => '2026-2027', 'year_level' => '1st', 'section' => 'A', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active', 'managed_team_id' => $team->id]);
    foreach (['CHESS-', 'BADMINTON-TEAM-'] as $prefix) {
        $event = racketFormatEvent($edition, $prefix.$suffix);
        $payload = [...$student->only(['first_name', 'middle_name', 'last_name', 'school_year', 'course_id', 'gender', 'year_level', 'section', 'status']), 'edition_sport_ids' => [$event->id]];
        $this->actingAs($admin)->put(route('admin.students.update', $student), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($gam)->put(route('gam.teams.players.sports.update', [$team, $student]), ['edition_sport_ids' => [$event->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('athlete_entries', ['student_id' => $student->id, 'edition_sport_id' => $event->id, 'team_id' => $team->id, 'status' => 'active']);
    }
    $label = $suffix === 'MEN' ? 'Men' : 'Women';
    $this->actingAs($gam)->get(route('gam.dashboard', ['team_id' => $team->id]))->assertOk()->assertSee('Badminton (Singles - '.$label.')', false);
})->with([['Male', 'MEN'], ['Female', 'WOMEN']]);

test('chess brackets group players by faction instead of creating a competitor for each player', function () {
    $edition = racketFormatEdition();
    app(RacketEventFormatService::class)->sync();
    $event = racketFormatEvent($edition, 'CHESS-MEN');
    foreach (['SEA', 'TERRA'] as $code) {
        $team = Team::create(['edition_id' => $edition->id, 'name' => $code, 'code' => $code, 'status' => 'active']);
        foreach (range(1, 2) as $number) {
            $student = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
            TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
            AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'status' => 'active']);
        }
    }
    app(RacketEventFormatService::class)->sync();
    app(BracketService::class)->initialize($event->fresh());
    expect($event->bracketCompetitors)->toHaveCount(2);
    foreach ($event->bracketCompetitors as $competitor) {
        expect($competitor->type)->toBe('team')->and($competitor->athlete_entry_ids)->toHaveCount(2);
    }
});
