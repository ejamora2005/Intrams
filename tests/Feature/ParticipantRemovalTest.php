<?php

use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use App\Models\ScheduleParticipant;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function participantRemovalFixture(string $type = 'team'): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Participant removal edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $sport = Sport::create(['name' => 'Removal '.$type, 'code' => 'REMOVAL-'.strtoupper($type), 'status' => 'active']);
    $event = $edition->editionSports()->create(['sport_id' => $sport->id, 'participant_type' => $type, 'game_mechanic' => 'round_robin', 'status' => 'active', 'scoring_rules' => ['point_system' => 'sports_major', 'placements' => StandingsService::DEFAULT_PLACEMENTS]]);
    $teams = collect(['Sea Dragons', 'Terraquatic Eagles'])->map(fn ($name, $index) => Team::create(['edition_id' => $edition->id, 'name' => $name, 'code' => 'REMOVE-'.$index, 'status' => 'active']));
    $students = Student::factory()->count(3)->create(['status' => 'active']);
    $pair = (string) Str::uuid();
    $entries = $students->map(function ($student, $index) use ($edition, $event, $teams, $admin, $type, $pair) {
        $team = $teams[$index < 2 ? 0 : 1];
        TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

        return AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'team_id' => $type === 'individual' ? null : $team->id,
            'pair_key' => $type === 'dual' && $index < 2 ? $pair : null, 'status' => 'active', 'assigned_by' => $admin->id,
            'assigned_at' => now(), 'medical_certificate_status' => 'verified', 'medical_certificate_reviewed_by' => $admin->id, 'medical_certificate_reviewed_at' => now(), 'medical_certificate_notes' => 'Existing approval']);
    });

    return [$admin, $edition, $event, $teams, $entries->map->fresh()];
}

function participantRemovalHistory(): array
{
    return collect(['students', 'team_members', 'competition_schedules', 'schedule_participants', 'competition_results', 'bracket_matches', 'bracket_competitors', 'sport_results', 'team_tallies'])
        ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
}

test('a scheduled player can be removed without deleting their registration or resetting the bracket', function () {
    [$admin, $edition, $event, $teams, $entries] = participantRemovalFixture();
    $competitor = BracketCompetitor::create(['edition_sport_id' => $event->id, 'type' => 'team', 'identity_key' => 'team:'.$teams[0]->id, 'team_id' => $teams[0]->id, 'label' => $teams[0]->name, 'athlete_entry_ids' => $entries->take(2)->pluck('id')->all()]);
    $match = BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'competitor_one_id' => $competitor->id, 'status' => 'pending']);
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'bracket_match_id' => $match->id, 'starts_at' => '2026-10-21 13:00:00', 'ends_at' => '2026-10-21 14:00:00', 'venue' => 'MPCC', 'status' => 'scheduled']);
    ScheduleParticipant::create(['competition_schedule_id' => $schedule->id, 'athlete_entry_id' => $entries[0]->id, 'slot' => 'one', 'status' => 'active']);
    $before = participantRemovalHistory();
    $others = $entries->slice(1)->map->getAttributes()->all();
    $this->actingAs($admin)->from(route('admin.sports.participants', ['sport' => $event->sport, 'edition_id' => $edition->id]))
        ->post(route('admin.sports.participants.bulk-remove', $event->sport), ['edition_id' => $edition->id, 'team_id' => $teams[0]->id, 'athlete_entry_ids' => [$entries[0]->id]])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.sports.participants', ['sport' => $event->sport, 'edition_id' => $edition->id, 'team_id' => $teams[0]->id]));
    expect($entries[0]->fresh()->status)->toBe('inactive')->and(participantRemovalHistory())->toBe($before)
        ->and($entries->slice(1)->map->fresh()->map->getAttributes()->all())->toBe($others);
    $page = $this->get(route('admin.sports.participants', ['sport' => $event->sport, 'edition_id' => $edition->id, 'team_id' => $teams[0]->id]))
        ->assertOk()->assertSee($entries[1]->student->full_name)->assertViewHas('selectedTeamId', $teams[0]->id);
    $dom = new DOMDocument;
    @$dom->loadHTML($page->getContent());
    expect((new DOMXPath($dom))->query('//input[@name="athlete_entry_ids[]" and @value="'.$entries[0]->id.'"]')->length)->toBe(0);
    $this->assertDatabaseHas('audit_logs', ['action' => 'athlete_entry.removed', 'auditable_id' => $entries[0]->id]);
});

test('selected registrations can be withdrawn after results while other players and declared history are retained', function (string $type) {
    [$admin, $edition, $event, $teams, $entries] = participantRemovalFixture($type);
    BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'winner_team_id' => $teams[0]->id, 'status' => 'completed']);
    app(StandingsService::class)->recordSportResult($event, [1 => $teams[0]->id], $admin);
    $before = participantRemovalHistory();
    $otherPlayers = $entries->slice(1)->map->getAttributes()->all();
    $this->actingAs($admin)->post(route('admin.sports.participants.bulk-remove', $event->sport), ['edition_id' => $edition->id, 'athlete_entry_ids' => [$entries[0]->id]])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($entries[0]->fresh()->status)->toBe('inactive')->and(participantRemovalHistory())->toBe($before)
        ->and($entries->slice(1)->map->fresh()->map->getAttributes()->all())->toBe($otherPlayers);
    if ($type === 'dual') {
        $this->get(route('admin.sports.participants', ['sport' => $event->sport, 'edition_id' => $edition->id]))->assertOk()->assertSee('Needs a partner');
    }
})->with(['team', 'individual', 'dual']);

test('removal validates the entire selection and does not modify another sport or an archived edition', function () {
    [$admin, $edition, $event, , $entries] = participantRemovalFixture();
    $otherSport = Sport::create(['name' => 'Other Sport', 'code' => 'OTHER-REMOVE', 'status' => 'active']);
    $otherEvent = $edition->editionSports()->create(['sport_id' => $otherSport->id, 'participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'active']);
    $other = AthleteEntry::create(['edition_sport_id' => $otherEvent->id, 'student_id' => $entries[0]->student_id, 'status' => 'active']);
    $before = DB::table('athlete_entries')->get()->toJson();
    $this->actingAs($admin)->post(route('admin.sports.participants.bulk-remove', $event->sport), ['edition_id' => $edition->id, 'athlete_entry_ids' => [$entries[0]->id, $other->id]])->assertSessionHasErrors('athlete_entry_ids');
    expect(DB::table('athlete_entries')->get()->toJson())->toBe($before);
    $this->post(route('admin.sports.participants.bulk-remove', $event->sport), ['edition_id' => $edition->id])->assertSessionHasErrors(['athlete_entry_ids' => 'Select at least one player to remove.']);
    $edition->update(['status' => 'archived']);
    $this->post(route('admin.sports.participants.bulk-remove', $event->sport), ['edition_id' => $edition->id, 'athlete_entry_ids' => [$entries[0]->id]])->assertSessionHasErrors('athlete_entry_ids');
    expect(DB::table('athlete_entries')->get()->toJson())->toBe($before);
});

test('a removed player is selectable again and can be restored without changing saved matches or medical history', function (string $type) {
    [$admin, $edition, $event, $teams, $entries] = participantRemovalFixture($type);
    BracketMatch::create(['edition_sport_id' => $event->id, 'bracket' => 'round_robin', 'round_number' => 1, 'match_number' => 1, 'status' => 'completed', 'winner_team_id' => $teams[0]->id]);
    $entries[0]->update(['status' => 'inactive']);
    $before = participantRemovalHistory();
    $approval = $entries[0]->only(['medical_certificate_status', 'medical_certificate_reviewed_by', 'medical_certificate_reviewed_at', 'medical_certificate_notes', 'pair_key']);
    $this->actingAs($admin)->get(route('admin.sports.participants.assign', ['sport' => $event->sport, 'edition_id' => $edition->id, 'team_id' => $type === 'individual' ? null : $teams[0]->id]))->assertOk()->assertSee($entries[0]->student->full_name);
    $this->post(route('admin.sports.participants.store', $event->sport), ['edition_id' => $edition->id, 'team_id' => $type === 'individual' ? null : $teams[0]->id,
        'student_ids' => $type === 'dual' ? $entries->take(2)->pluck('student_id')->all() : [$entries[0]->student_id]])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($entries[0]->fresh()->status)->toBe('active')->and(participantRemovalHistory())->toBe($before)
        ->and($entries[0]->fresh()->only(array_keys($approval)))->toEqual($approval);
})->with(['team', 'individual', 'dual']);
