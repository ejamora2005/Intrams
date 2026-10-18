<?php

use App\Models\AthleteEntry;
use App\Models\CompetitionSchedule;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\SportScoreSheetService;
use Illuminate\Http\UploadedFile;

function sportSheetScenario(string $code = 'CHESS-MEN', string $role = 'admin'): array
{
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);
    $edition = IntramuralEdition::create(['name' => 'Scoring 2026', 'school_year' => '2026-2027', 'starts_on' => '2026-10-19', 'ends_on' => '2026-10-23', 'status' => 'active']);
    $sport = Sport::create(['name' => $code, 'code' => $code, 'status' => 'active']);
    $event = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'status' => 'active']);

    return compact('user', 'edition', 'sport', 'event');
}

test('every configured sport has a discipline specific sheet for both scoring roles', function () {
    ['user' => $admin, 'edition' => $edition] = sportSheetScenario();
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $seen = [];
    foreach (config('intramurals.default_competitions') as $definition) {
        if (str_starts_with($definition['code'], 'CULT-')) {
            continue;
        }
        $sport = Sport::firstOrCreate(['code' => $definition['code']], ['name' => $definition['name'], 'participant_category' => $definition['participant_category'] ?? 'open', 'status' => 'active']);
        EditionSport::firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => $definition['participant_type'] ?? 'team', 'game_mechanic' => 'custom', 'status' => 'active']);
        $profile = app(SportScoreSheetService::class)->profile($sport);
        expect($profile['key'])->not->toBe('custom', $sport->code);
        $seen[] = $profile['key'];
        foreach (['admin' => $admin, 'tabulator' => $tabulator] as $role => $user) {
            $this->actingAs($user)->get(route($role.'.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
                ->assertOk()->assertSee('data-score-sheet-export', false)
                ->assertSee(route($role.'.sports.score-sheet.download', $sport), false);
        }
    }
    expect(array_unique($seen))->toHaveCount(19);
});

test('individual sheets resolve the faction from membership and exclude inactive or withdrawn players', function () {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport, 'event' => $event] = sportSheetScenario('BADMINTON-DOUBLES-WOMEN');
    $course = Course::create(['name' => 'Marine Biology', 'code' => 'MB', 'status' => 'active']);
    $team = Team::create(['edition_id' => $edition->id, 'name' => 'Sea Dragons', 'code' => 'SEA', 'status' => 'active']);
    foreach (['active', 'withdrawn', 'inactive'] as $status) {
        $student = Student::factory()->create(['course_id' => $course->id, 'first_name' => ucfirst($status), 'middle_name' => null, 'last_name' => 'Athlete', 'status' => $status === 'inactive' ? 'inactive' : 'active']);
        TeamMember::create(['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        AthleteEntry::create(['edition_sport_id' => $event->id, 'team_id' => null, 'student_id' => $student->id, 'status' => $status === 'withdrawn' ? 'withdrawn' : 'active', 'assigned_by' => $user->id, 'assigned_at' => now()]);
    }
    $response = $this->actingAs($user)->get(route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))->assertOk();
    $response->assertSee('Active Athlete')->assertSee('Sea Dragons')->assertSee('MB')->assertDontSee('Withdrawn Athlete')->assertDontSee('Inactive Athlete');
    expect($response->viewData('profile')['players_per_side'])->toBe(2)
        ->and($response->viewData('teams')->first()['players'])->toHaveCount(1);
});

test('track results paginate the complete entry list without omitting athletes', function () {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport, 'event' => $event] = sportSheetScenario('100M-RUN-MEN');
    foreach (range(1, 17) as $number) {
        $student = Student::factory()->create(['first_name' => 'Runner', 'middle_name' => null, 'last_name' => sprintf('%02d', $number), 'status' => 'active']);
        AthleteEntry::create(['edition_sport_id' => $event->id, 'student_id' => $student->id, 'status' => 'active', 'assigned_by' => $user->id, 'assigned_at' => now()]);
    }
    $response = $this->actingAs($user)->get(route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id, 'sheet_page' => 2]))->assertOk();
    expect($response->viewData('participants'))->toHaveCount(1)
        ->and($response->viewData('participants')->first()['name'])->toBe('Runner 17');
    $response->assertSee('sheet 2 of 2')->assertSee('Field/Oval')->assertSee('value="Runner 17"', false)->assertSee('Finish time');
});

test('sheets prefill date and exact time only from the selected sports own session', function () {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport, 'event' => $event] = sportSheetScenario();
    $schedule = CompetitionSchedule::create(['edition_sport_id' => $event->id, 'title' => 'Chess finals', 'is_activity' => false, 'starts_at' => '2026-10-20 14:35:00', 'ends_at' => '2026-10-20 16:00:00', 'venue' => 'SSC Hall', 'status' => 'scheduled']);
    $url = route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id, 'schedule_id' => $schedule->id]);
    $this->actingAs($user)->get($url)->assertOk()->assertSee('value="2026-10-20"', false)->assertSee('value="2:35 PM"', false)->assertSee('value="SSC Hall"', false);
    $other = Sport::create(['name' => 'Other', 'code' => 'OTHER', 'status' => 'active']);
    EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $other->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active']);
    $this->get(route('admin.sports.score-sheet', ['sport' => $other, 'edition_id' => $edition->id, 'schedule_id' => $schedule->id]))->assertNotFound();
});

test('score sheets require scoring staff, a configured edition and a sporting event', function () {
    ['user' => $admin, 'edition' => $edition, 'sport' => $sport] = sportSheetScenario();
    $this->get(route('admin.sports.score-sheet', $sport))->assertRedirect(route('login'));
    $gam = User::factory()->create(['role' => 'gam', 'status' => 'active']);
    $this->actingAs($gam)->get(route('tabulator.sports.score-sheet', $sport))->assertForbidden();
    $culture = Sport::create(['name' => 'Painting', 'code' => 'CULT-PAINT', 'status' => 'active']);
    EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $culture->id, 'participant_type' => 'team', 'game_mechanic' => 'custom', 'status' => 'active']);
    $this->actingAs($admin)->get(route('admin.sports.score-sheet', $culture))->assertNotFound();
    $unconfigured = Sport::create(['name' => 'Chess next year', 'code' => 'CHESS-FUTURE', 'status' => 'active']);
    $this->get(route('admin.sports.score-sheet', $unconfigured))->assertNotFound();
    $edition->update(['status' => 'draft']);
    $tabulator = User::factory()->create(['role' => 'tabulator', 'status' => 'active']);
    $this->actingAs($tabulator)->get(route('tabulator.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))->assertNotFound();
});

test('all export formats preserve A4 orientation and the filled sheet image', function (string $format, string $code, string $orientation) {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport] = sportSheetScenario($code, 'tabulator');
    $landscape = $orientation === 'landscape';
    $preview = UploadedFile::fake()->image('filled-sheet.png', $landscape ? 2246 : 1588, $landscape ? 1588 : 2246);
    $response = $this->actingAs($user)->post(route('tabulator.sports.score-sheet.download', $sport), ['edition_id' => $edition->id, 'format' => $format, 'preview_image' => $preview])->assertOk();
    if ($format === 'pdf') {
        expect($response->getContent())->toStartWith('%PDF');
        preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)/', $response->getContent(), $page);
        expect((float) $page[1])->toEqualWithDelta(($landscape ? 297 : 210) * 72 / 25.4, .02);
    } else {
        $path = tempnam(sys_get_temp_dir(), 'sport-sheet-');
        file_put_contents($path, $response->getContent());
        try {
            $zip = new ZipArchive;
            expect($zip->open($path))->toBeTrue();
            expect($zip->getFromName(($format === 'docx' ? 'word' : 'xl').'/media/score-sheet.png'))->toBe(file_get_contents($preview->getRealPath()));
            if ($format === 'xlsx') {
                preg_match('/<sheet name="([^"]+)"/', $zip->getFromName('xl/workbook.xml'), $name);
                expect(mb_strlen(html_entity_decode($name[1])))->toBeLessThanOrEqual(31);
            }
            $zip->close();
        } finally {
            unlink($path);
        }
    }
    $this->assertDatabaseCount('sport_results', 0);
})->with([
    ['pdf', 'CHESS-MEN', 'portrait'], ['docx', 'CHESS-MEN', 'portrait'], ['xlsx', 'CHESS-MEN', 'portrait'],
    ['pdf', 'RELAY-4X100-WOMEN', 'landscape'], ['docx', 'RELAY-4X100-WOMEN', 'landscape'], ['xlsx', 'RELAY-4X100-WOMEN', 'landscape'],
]);

test('exports reject an incomplete or wrong orientation capture', function () {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport] = sportSheetScenario('HIGH-JUMP-MEN');
    $this->actingAs($user)->postJson(route('admin.sports.score-sheet.download', $sport), ['edition_id' => $edition->id, 'format' => 'pdf', 'preview_image' => UploadedFile::fake()->image('wrong.png', 600, 800)])
        ->assertUnprocessable()->assertJsonValidationErrors('preview_image');
});

test('old basketball and volleyball links open the correct specialty sheet', function (string $code, string $oldRoute) {
    ['user' => $user, 'edition' => $edition, 'sport' => $sport] = sportSheetScenario($code);
    $sport->update(['name' => str_contains($code, 'BASKET') ? 'Basketball 3x3 (Men)' : 'Beach Volleyball (Women)']);
    $this->actingAs($user)->get(route('admin.sports.'.$oldRoute, ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertRedirect(route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]));
})->with([['BASKET-3X3-MEN', 'basketball-score-sheet'], ['BEACH-VOLLEYBALL-WOMEN', 'volleyball-score-sheet']]);
