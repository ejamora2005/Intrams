<?php

use App\Models\AthleteEntry;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;

function volleyballScoreSheetScenario(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Volleyball Score Sheet Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Volleyball', 'code' => 'VOLLEYBALL', 'status' => 'active']);
    EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);
    $editionSport = EditionSport::query()->where('edition_id', $edition->id)->where('sport_id', $sport->id)->firstOrFail();
    $course = Course::query()->create(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT', 'status' => 'active']);
    $team = Team::query()->create(['edition_id' => $edition->id, 'course_id' => $course->id, 'name' => 'BLUE SPIKERS', 'code' => 'BLUE-SPIKERS', 'status' => 'active']);
    $student = Student::factory()->create([
        'course_id' => $course->id,
        'student_number' => '2026-00001',
        'first_name' => 'Alex',
        'middle_name' => null,
        'last_name' => 'Santos',
        'status' => 'active',
    ]);
    AthleteEntry::query()->create([
        'edition_sport_id' => $editionSport->id,
        'team_id' => $team->id,
        'student_id' => $student->id,
        'status' => 'active',
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    return compact('admin', 'edition', 'sport');
}

test('an administrator can open the volleyball reference sheet on A4 landscape paper', function () {
    ['admin' => $admin, 'edition' => $edition, 'sport' => $sport] = volleyballScoreSheetScenario();

    $this->actingAs($admin)
        ->get(route('admin.sports.volleyball-score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Volleyball score sheet')
        ->assertSee('images/volleyball-score-sheet-reference.png')
        ->assertSee('data-volleyball-score-sheet', false)
        ->assertSee('id="volleyball-teams-data"', false)
        ->assertSee('decoding="async" fetchpriority="high"', false)
        ->assertSee('A4 landscape · 297 × 210 mm')
        ->assertSee('width: 297mm; height: 210mm;', false)
        ->assertSee('Prefill Home Team')
        ->assertSee('Prefill Visitor Team')
        ->assertSee('BLUE SPIKERS')
        ->assertSee('Alex Santos')
        ->assertSee('Santos. A.')
        ->assertDontSee('2026-00001')
        ->assertSee('BSIT')
        ->assertSee('data-volleyball-team-name="home"', false)
        ->assertSee('data-volleyball-player-name="visitor"', false)
        ->assertSee('Player<br>Name', false)
        ->assertSee('Word (.docx)')
        ->assertSee('Excel (.xlsx)')
        ->assertSee('PDF (.pdf)');
});

test('the volleyball score sheet is unavailable for non-volleyball sports', function () {
    ['admin' => $admin, 'edition' => $edition] = volleyballScoreSheetScenario();
    $basketball = Sport::query()->create(['name' => 'Basketball 5x5', 'code' => 'BASKETBALL-5X5', 'status' => 'active']);
    EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $basketball->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sports.volleyball-score-sheet', ['sport' => $basketball, 'edition_id' => $edition->id]))
        ->assertNotFound();
});

test('volleyball downloads preserve the full A4 landscape image in each format', function (string $format) {
    ['admin' => $admin, 'edition' => $edition, 'sport' => $sport] = volleyballScoreSheetScenario();
    $preview = Illuminate\Http\UploadedFile::fake()->image('volleyball-preview.png', 2246, 1588);

    $response = $this->actingAs($admin)->post(route('admin.sports.volleyball-score-sheet.download', $sport), [
        'edition_id' => $edition->id,
        'format' => $format,
        'preview_image' => $preview,
    ]);

    $mime = match ($format) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    };
    $response->assertOk()
        ->assertHeader('content-type', $mime)
        ->assertHeader('content-disposition', 'attachment; filename="volleyball-score-sheet-'.$edition->id.'.'.$format.'"');

    if ($format === 'pdf') {
        expect($response->getContent())->toStartWith('%PDF');
        preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)/', $response->getContent(), $page);
        expect((float) $page[1])->toEqualWithDelta(297 * 72 / 25.4, .02)
            ->and((float) $page[2])->toEqualWithDelta(210 * 72 / 25.4, .02);
    } else {
        $path = tempnam(sys_get_temp_dir(), 'volleyball-office-test-');
        file_put_contents($path, $response->getContent());
        try {
            $zip = new ZipArchive;
            expect($zip->open($path))->toBeTrue();
            $prefix = $format === 'xlsx' ? 'xl' : 'word';
            expect($zip->getFromName($prefix.'/media/score-sheet.png'))->toBe(file_get_contents($preview->getRealPath()));
            $document = $zip->getFromName($format === 'xlsx' ? 'xl/drawings/drawing1.xml' : 'word/document.xml');
            expect($document)->toContain('cx="10692000" cy="7560000"');
            if ($format === 'xlsx') {
                expect($zip->getFromName('xl/worksheets/sheet1.xml'))->toContain('orientation="landscape"')
                    ->and($zip->getFromName('xl/workbook.xml'))->toContain('Volleyball score sheet');
            } else {
                expect($document)->toContain('<w:pgSz w:w="16838" w:h="11906"/>');
            }
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'volleyball_score_sheet.downloaded',
    ]);
})->with(['xlsx', 'docx', 'pdf']);
