<?php

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionResult;
use App\Models\CompetitionSchedule;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\ScheduleParticipant;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamTally;
use App\Models\User;
use App\Services\PublicProgrammeService;
use App\Services\PublicScheduleService;
use Illuminate\Support\Facades\DB;

function officialProgrammeEdition(): IntramuralEdition
{
    return IntramuralEdition::create(['name' => config('intramurals.default_edition.name'), 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
}

test('public programme accounts for every event cell in the supplied Sked sheet without inventing times or venues', function () {
    $source = json_decode(file_get_contents(base_path('tests/Fixtures/public-programme-sked-2026-10-20.json')), true, 512, JSON_THROW_ON_ERROR)['cells'];
    $covered = [];
    $events = collect(config('public_programme.events'));
    foreach ($events as $event) {
        preg_match('/^([A-Z]+)(\d+)(?::([A-Z]+)(\d+))?$/', $event['cells'], $match);
        $values = [];
        for ($row = (int) $match[2]; $row <= (int) ($match[4] ?? $match[2]); $row++) {
            $cell = $match[1].$row;
            $covered[$cell] = true;
            if (isset($source[$cell])) {
                $values[] = $source[$cell];
            }
        }
        expect(array_values(array_unique(array_filter($values, 'is_string'))))->toBe([$event['event']]);
        $day = match ($match[1]) {
            'A' => '2026-10-19', 'B', 'C', 'D' => '2026-10-20', 'E', 'F' => '2026-10-21',
            'G', 'H', 'I' => '2026-10-22', 'J', 'K', 'L' => '2026-10-23',
        };
        expect($event['date'])->toBe($day)->and($event['venue'])->toBe($match[1] === 'A' ? null : $source[$match[1].'4']);
        $times = array_values(array_filter($values, 'is_numeric'));
        if ($times !== []) {
            $minutes = (int) round($times[0] * 1440);
            $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            expect($event['starts_at'] ?? $event['ends_at'] ?? null)->toBe($time);
        } else {
            expect(isset($event['starts_at']))->toBeFalse()->and(isset($event['ends_at']))->toBeFalse();
        }
    }
    foreach ($source as $cell => $value) {
        if ((int) preg_replace('/\D/', '', $cell) >= 6 && is_string($value) && ! in_array($value, ['x', 'AM', 'PM'], true)) {
            expect(isset($covered[$cell]))->toBeTrue();
        }
    }
    expect($events->groupBy('date')->map->count()->all())->toBe(['2026-10-19' => 2, '2026-10-20' => 7, '2026-10-21' => 8, '2026-10-22' => 6, '2026-10-23' => 5]);
});

test('public programme replaces only the public October timetable and leaves all saved sports schedules and scores intact', function () {
    $edition = officialProgrammeEdition();
    $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $sport = Sport::create(['name' => 'Internal scored sport', 'code' => 'BASKET-5X5-MEN', 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'scoring_rules' => ['placements' => [['placement' => 1, 'points' => 77]]], 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    $student = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);
    TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
    AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $team->id, 'status' => 'active']);
    $match = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'team_one_id' => $team->id, 'winner_team_id' => $team->id, 'status' => 'completed']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'bracket_match_id' => $match->id, 'title' => 'INTERNAL SAVED MATCH', 'starts_at' => '2026-10-20 08:13:00', 'ends_at' => '2026-10-20 09:13:00', 'venue' => 'MPCC', 'status' => 'completed']);
    CompetitionSchedule::create(['edition_sport_id' => $event->id, 'title' => 'INTERNAL EXTRA DATE', 'starts_at' => '2026-10-24 08:00:00', 'ends_at' => '2026-10-24 09:00:00', 'status' => 'scheduled']);
    ScheduleParticipant::create(['competition_schedule_id' => $schedule->id, 'team_id' => $team->id, 'slot' => 'A', 'status' => 'active']);
    CompetitionResult::create(['competition_schedule_id' => $schedule->id, 'result_data' => ['winner_team_id' => $team->id], 'status' => 'approved', 'submitted_by' => $user->id, 'submitted_at' => '2026-10-20 01:13:00']);
    SportResult::create(['edition_sport_id' => $event->id, 'placements_json' => ['1' => $team->id], 'points_json' => [$team->id => 77], 'submitted_by' => $user->id, 'submitted_at' => '2026-10-20 01:13:00']);
    TeamTally::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'points' => 77, 'updated_at' => '2026-10-20 01:13:00']);
    $tables = ['intramural_editions', 'sports', 'edition_sports', 'students', 'team_members', 'athlete_entries', 'competition_schedules', 'schedule_participants', 'competition_results', 'bracket_matches', 'bracket_competitors', 'sport_results', 'team_tallies'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    $before = $snapshot();
    foreach (['2026-10-19' => 2, '2026-10-20' => 6, '2026-10-21' => 8, '2026-10-22' => 5, '2026-10-23' => 5] as $date => $count) {
        $data = app(PublicScheduleService::class)->today($date);
        expect($data['todaySchedules'])->toHaveCount($count)
            ->and($data['programmeSource']['worksheet'])->toBe('Sked')
            ->and($data['todaySchedules']->pluck('game')->filter())->toBeEmpty();
        $this->get(route('login', ['date' => $date]))->assertOk()->assertSee('View programme sheet')->assertDontSee('INTERNAL SAVED MATCH');
    }
    expect($snapshot())->toBe($before)->and($match->fresh()->schedule->id)->toBe($schedule->id);
    $outside = app(PublicScheduleService::class)->today('2026-10-24');
    expect($outside['todaySchedules'])->toBeEmpty()->and($outside['availableDates'])->toBe(['2026-10-19', '2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23']);
    $this->actingAs($user)->get(route('admin.competition.index'))->assertOk()->assertSee('INTERNAL SAVED MATCH');
    expect($snapshot())->toBe($before);
});

test('programme labels retain confirmed cutoffs evening time and all factions for cultural blocks', function () {
    $edition = officialProgrammeEdition();
    foreach (['Sea Dragons', 'Terraquatic Eagles', 'Trojan Warriors'] as $index => $name) {
        Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'PROGRAMME-'.$index, 'status' => 'active']);
    }
    $service = app(PublicScheduleService::class);
    expect($service->today('2026-10-19')['todaySchedules']->last())->toMatchArray(['event' => 'Badminton', 'time' => 'PM', 'cutoff' => 'Until 3:00 PM', 'venue' => 'TBA']);
    $basketball = $service->today('2026-10-20')['todaySchedules']->first(fn ($event) => $event['event'] === 'Basketball 3x3 (MW)' && $event['period'] === 'Afternoon');
    expect($basketball)->toMatchArray(['time' => 'PM', 'cutoff' => 'Until 2:00 PM']);
    $awarding = $service->today('2026-10-21')['todaySchedules']->last();
    expect($awarding)->toMatchArray(['event' => 'Awarding Mugna', 'time' => '7:00 PM', 'period' => 'Evening', 'category' => 'activities']);
    foreach ($service->today('2026-10-21')['todaySchedules']->where('category', 'cultural') as $event) {
        expect($event['competitor_teams'])->toHaveCount(3);
    }
    $this->get('/login?date=2026-10-21')->assertOk()->assertSee('7:00 PM')->assertSee('Evening')->assertDontSee('Digital Poster Contest')->assertDontSee('Short Film Festival');
});

test('other editions and an absent active edition keep their existing public schedule behavior', function () {
    $edition = officialProgrammeEdition();
    $service = app(PublicProgrammeService::class);
    expect(app(PublicScheduleService::class)->today('2026-10-24')['todaySchedules'])->toBeEmpty();
    $edition->update(['name' => 'Separate competition']);
    expect($service->datesFor($edition))->toBeEmpty()->and(app(PublicScheduleService::class)->today('2026-10-20')['programmeSource'])->toBeNull();
    $edition->update(['status' => 'archived']);
    $data = app(PublicScheduleService::class)->today('2026-10-20');
    expect($data['programmeSource'])->toBeNull()->and($data['todaySchedules'])->toBeEmpty();
});
