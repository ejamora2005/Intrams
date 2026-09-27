<?php

use App\Models\AthleteEntry;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;

test('an administrator can open a downloadable basketball score sheet with registered rosters', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Basketball Score Sheet Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Basketball 5x5', 'code' => 'BASKETBALL-5X5', 'status' => 'active']);
    $editionSport = EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);
    $course = Course::query()->create(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT', 'status' => 'active']);
    $team = Team::query()->create(['edition_id' => $edition->id, 'course_id' => $course->id, 'name' => 'BLUE WARRIORS', 'code' => 'BLUEWARRIORS', 'status' => 'active']);
    $student = Student::factory()->create(['status' => 'active', 'course_id' => $course->id, 'first_name' => 'Jordan', 'last_name' => 'Rivera', 'year_level' => '3rd', 'section' => 'B']);
    AthleteEntry::query()->create([
        'edition_sport_id' => $editionSport->id,
        'team_id' => $team->id,
        'student_id' => $student->id,
        'status' => 'active',
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('FEDERATION INTERNATIONALE DE BASKETBALL')
        ->assertSee('images/fiba-basketball.webp')
        ->assertSee('BLUE WARRIORS')
        ->assertSee('BSIT')
        ->assertSee('Course')->assertSee('A4 paper preview')->assertSee('width: 210mm; height: 297mm;', false)
        ->assertSee('RUNNING SCORE')
        ->assertSee('score-entry')
        ->assertSee('placeholder="1"', false)
        ->assertSee('.fiba-roster .player-no { text-align: center; width: 5mm; }', false)
        ->assertSee('.fiba-roster .fouls { width: 20mm; }', false)
        ->assertDontSee('target="_blank"', false)
        ->assertSee('Word (.docx)')->assertSee('Excel (.xlsx)')->assertSee('PDF (.pdf)')
        ->assertDontSee('Print score sheet');
});

test('the basketball score sheet is unavailable for non-basketball sports', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Non Basketball Edition',
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

    $this->actingAs($admin)
        ->get(route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]))
        ->assertNotFound();
});

test('an administrator can directly download each selected format with the full A4 paper image', function (string $format) {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $edition = IntramuralEdition::query()->create([
        'name' => 'Download Score Sheet Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'active',
    ]);
    $sport = Sport::query()->create(['name' => 'Basketball 3x3', 'code' => 'BASKETBALL-3X3', 'status' => 'active']);
    EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'status' => 'active',
    ]);

    $preview = \Illuminate\Http\UploadedFile::fake()->image('preview.png', 1588, 2246);
    $response = $this->actingAs($admin)->post(route('admin.sports.basketball-score-sheet.download', $sport), [
        'edition_id' => $edition->id,
        'team_a_name' => 'BLUE WARRIORS',
        'team_b_name' => 'RED HAWKS',
        'final_score_a' => '21',
        'final_score_b' => '18',
        'format' => $format,
        'preview_image' => $preview,
    ]);

    $mime = match ($format) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    };
    $response->assertOk()->assertHeader('content-type', $mime)
        ->assertHeader('content-disposition', 'attachment; filename="basketball-score-sheet-'.$edition->id.'.'.$format.'"');
    if ($format === 'pdf') {
        expect($response->getContent())->toStartWith('%PDF')
            ->and(preg_match_all('/\\/Type\\s*\\/Page\\b/', $response->getContent()))->toBe(1);
        preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)/', $response->getContent(), $page);
        expect((float) $page[1])->toEqualWithDelta(210 * 72 / 25.4, .02)
            ->and((float) $page[2])->toEqualWithDelta(297 * 72 / 25.4, .02);
    } else {
        $path = tempnam(sys_get_temp_dir(), 'office-test-');
        file_put_contents($path, $response->getContent());
        try {
            $zip = new ZipArchive();
            expect($zip->open($path))->toBeTrue();
            $prefix = $format === 'xlsx' ? 'xl' : 'word';
            expect($zip->getFromName($prefix.'/media/score-sheet.png'))->toBe(file_get_contents($preview->getRealPath()));
            $document = $zip->getFromName($format === 'xlsx' ? 'xl/drawings/drawing1.xml' : 'word/document.xml');
            expect($document)->toContain('cx="7560000" cy="10692000"');
            if ($format === 'xlsx') {
                expect($zip->getFromName('xl/worksheets/sheet1.xml'))->toContain('paperSize="9"')->toContain('fitToWidth="1" fitToHeight="1"');
                expect($zip->getFromName('xl/workbook.xml'))->toContain('$A$1:$A$3');
            } else {
                expect($document)->toContain('<w:pgSz w:w="11906" w:h="16838"/>')->toContain('relativeFrom="page"')->not->toContain('<w:tbl>');
            }
            foreach (range(0, $zip->numFiles - 1) as $index) {
                if (!str_ends_with($zip->getNameIndex($index), '.png')) {
                    expect(simplexml_load_string($zip->getFromIndex($index)))->not->toBeFalse();
                }
            }
            $zip->close();
        } finally {
            unlink($path);
        }
    }
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'basketball_score_sheet.downloaded']);
    $this->actingAs($admin)->postJson(route('admin.sports.basketball-score-sheet.download', $sport), ['edition_id' => $edition->id, 'format' => 'exe'])->assertUnprocessable();
})->with(['xlsx', 'docx', 'pdf']);

test('both rosters have a blank counter heading and twelve numbered rows before players and course', function () {
    $html = view('admin.sports.partials.basketball-score-sheet', ['logoSource' => '', 'isPdf' => false])->render();
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//table[@class="fiba-roster"]') as $table) {
        $headers = $xpath->query('./thead/tr/th', $table);
        expect(trim($headers->item(0)->textContent))->toBe('')
            ->and(trim($headers->item(1)->textContent))->toBe('Players')
            ->and(trim($headers->item(3)->textContent))->toBe('Course');
        $rows = $xpath->query('./tbody/tr', $table);
        expect($rows->length)->toBe(12);
        foreach ($rows as $index => $row) {
            expect(trim($xpath->query('./td', $row)->item(0)->textContent))->toBe((string) ($index + 1));
        }
    }
});
