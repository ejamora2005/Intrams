<?php

namespace Database\Seeders;

use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\EligibilityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

abstract class TeamRosterSeeder extends Seeder
{
    private const SPORT_ALIASES = [
        '100m run' => ['100M-RUN'],
        '200m run' => ['200M-RUN'],
        '3x3' => ['BASKET-3X3'],
        '3x3 basketball' => ['BASKET-3X3'],
        '3x3 basketball men' => ['BASKET-3X3-MEN'],
        '3x3 basketball women' => ['BASKET-3X3-WOMEN'],
        '3x3 baketball' => ['BASKET-3X3'],
        '400m run' => ['400M-RUN'],
        '4x100 relay' => ['RELAY-4X100'],
        '4x400 relay' => ['RELAY-4X400'],
        '5x5 baketball' => ['BASKET-5X5'],
        '5x5 basketball' => ['BASKET-5X5'],
        '5x5 basketball men' => ['BASKET-5X5-MEN'],
        '5x5 basketball women' => ['BASKET-5X5-WOMEN'],
        '5x5' => ['BASKET-5X5'],
        'badminton' => ['BADMINTON'],
        'badminton men' => ['BADMINTON-DOUBLES-MEN'],
        'badminton women' => ['BADMINTON-DOUBLES-WOMEN'],
        'badminton d' => ['BADMINTON-DOUBLES'],
        'badminton d men' => ['BADMINTON-DOUBLES-MEN'],
        'badminton doubles' => ['BADMINTON-DOUBLES'],
        'badminton s' => ['BADMINTON-SINGLES'],
        'badminton singles' => ['BADMINTON-SINGLES'],
        'badminton s men' => ['BADMINTON-SINGLES-MEN'],
        'badminton s women' => ['BADMINTON-SINGLES-WOMEN'],
        'badminton singles men' => ['BADMINTON-SINGLES-MEN'],
        'badminton singles women' => ['BADMINTON-SINGLES-WOMEN'],
        'balak' => ['CULT-BALAKWERTE'],
        'balakwerte' => ['CULT-BALAKWERTE'],
        'basketball' => ['BASKET-5X5'],
        'basketbal 5x5' => ['BASKET-5X5'],
        'basketball 3x3' => ['BASKET-3X3'],
        'basketball 3x3 men' => ['BASKET-3X3-MEN'],
        'basketball 3x3 women' => ['BASKET-3X3-WOMEN'],
        'basketball 5x5' => ['BASKET-5X5'],
        'basketball men 5x5' => ['BASKET-5X5-MEN'],
        'basketball men 5 5' => ['BASKET-5X5-MEN'],
        'basketball 5x5 men' => ['BASKET-5X5-MEN'],
        'basketball 5x5 women' => ['BASKET-5X5-WOMEN'],
        'basketball 5v5' => ['BASKET-5X5'],
        'basketball 6x6' => ['VOLLEYBALL'],
        'basketball and triple jump' => ['BASKET-5X5', 'TRIPLE-JUMP'],
        'basketball festival dancer sayawerte' => ['BASKET-5X5', 'CULT-FEST-DANCE', 'CULT-SAYAWERTE-DANCE'],
        'basketball festival dancer' => ['BASKET-5X5', 'CULT-FEST-DANCE'],
        'baseball' => ['BASEBALL'],
        'bseball' => ['BASEBALL'],
        'beach volleyball' => ['BEACH-VOLLEYBALL'],
        'beach volley' => ['BEACH-VOLLEYBALL'],
        'beachvolley' => ['BEACH-VOLLEYBALL'],
        'beachvolleyball' => ['BEACH-VOLLEYBALL'],
        'beach volleyball men' => ['BEACH-VOLLEYBALL-MEN'],
        'beach volleyball women' => ['BEACH-VOLLEYBALL-WOMEN'],
        'chess' => ['CHESS'],
        'chess men' => ['CHESS-MEN'],
        'chess women' => ['CHESS-WOMEN'],
        'charcoal rendering' => ['CULT-CHARCOAL'],
        'collaborative painting' => ['CULT-COLLAB-PAINT'],
        'digital photography' => ['CULT-DIGI-PHOTO'],
        'digital photograpy' => ['CULT-DIGI-PHOTO'],
        'digital poster making' => ['CULT-DIGITAL-POSTER'],
        'discus singswerte' => ['DISCUS-THROW', 'CULT-SING-SOLO'],
        'basketball shotput festival' => ['BASKET-5X5', 'SHOT-PUT', 'CULT-FEST-DANCE'],
        'discuss throw food committee' => ['DISCUS-THROW'],
        'discuss' => ['DISCUS-THROW'],
        'discuss throw' => ['DISCUS-THROW'],
        'discus throw' => ['DISCUS-THROW'],
        'duet' => ['CULT-SING-DUET'],
        'digital poster' => ['CULT-DIGITAL-POSTER'],
        'e sport' => ['ESPORT-ML'],
        'e esport mlbb' => ['ESPORT-ML'],
        'e sport mlbb' => ['ESPORT-ML'],
        'e sport mobile legend' => ['ESPORT-ML'],
        'e sport men' => ['ESPORT-ML-MEN'],
        'e sport women' => ['ESPORT-ML-WOMEN'],
        'e sport mlbb men' => ['ESPORT-ML-MEN'],
        'e sport mlbb girls' => ['ESPORT-ML-WOMEN'],
        'e sports mobile legend' => ['ESPORT-ML'],
        'e espor t' => ['ESPORT-ML'],
        'esports' => ['ESPORT-ML'],
        'film' => ['CULT-SHORT-FILM'],
        'festival' => ['CULT-FEST-DANCE'],
        'festival dancer' => ['CULT-FEST-DANCE'],
        'festival dancers' => ['CULT-FEST-DANCE'],
        'festival dance' => ['CULT-FEST-DANCE'],
        'festival dance dancer' => ['CULT-FEST-DANCE'],
        'festival dance props' => [],
        'festival queen' => ['CULT-STUDENT-PAGEANT'],
        'festival props' => [],
        'festival propsmen' => [],
        'festival propswoman tikarol creare' => [],
        'festival props men' => [],
        'festival props pakulo' => ['CULT-FACTION-PAKULO'],
        'festival props men pakulo' => ['CULT-FACTION-PAKULO'],
        'festival dancer basketball' => ['CULT-FEST-DANCE', 'BASKET-5X5'],
        'festival dancer runs' => ['CULT-FEST-DANCE', 'RUNS'],
        'festival dancer sayawerte' => ['CULT-FEST-DANCE', 'CULT-SAYAWERTE-DANCE'],
        'festival dancer basketball sayawerte' => ['CULT-FEST-DANCE', 'BASKET-5X5', 'CULT-SAYAWERTE-DANCE'],
        'food committee' => [],
        'food com' => [],
        'faction pakulo' => ['CULT-FACTION-PAKULO'],
        'gen z balitaw' => ['CULT-MASHUP-BALITAW'],
        'high jump' => ['HIGH-JUMP'],
        'infographics' => ['CULT-INFOGRAPHICS'],
        'isiphenyo' => ['CULT-QUIZ'],
        'isphenyo' => ['CULT-QUIZ'],
        'javelin' => ['JAVELIN-THROW'],
        'javelin throw' => ['JAVELIN-THROW'],
        'javelin throw food committee' => ['JAVELIN-THROW'],
        'long jump' => ['LONG-JUMP'],
        'mobile legend' => ['ESPORT-ML'],
        'mobile legends men' => ['ESPORT-ML-MEN'],
        'mobile legends women' => ['ESPORT-ML-WOMEN'],
        'mobile legends' => ['ESPORT-ML'],
        'balitaw' => ['CULT-MASHUP-BALITAW'],
        'modified volleyball pageant assistant' => ['MODIFIED-VOLLEYBALL'],
        'modified voleyball pageant assistant' => ['MODIFIED-VOLLEYBALL'],
        'pageant' => ['CULT-STUDENT-PAGEANT'],
        'pageant candidate' => ['CULT-STUDENT-PAGEANT'],
        'pageant assistant' => [],
        'pageant committee' => [],
        'pageant festival' => ['CULT-STUDENT-PAGEANT', 'CULT-FEST-DANCE'],
        'pakulo' => ['CULT-FACTION-PAKULO'],
        'propsmen pakulo' => ['CULT-FACTION-PAKULO'],
        'photography' => ['CULT-DIGI-PHOTO'],
        'propsmen' => [],
        'russian baseball' => ['RUSSIAN-SOFTBALL'],
        'russian festival props' => ['RUSSIAN-SOFTBALL'],
        'russian festival props pakulo' => ['RUSSIAN-SOFTBALL', 'CULT-FACTION-PAKULO'],
        'modified volleyball' => ['MODIFIED-VOLLEYBALL'],
        'modified volleyball men' => ['MODIFIED-VOLLEYBALL-MEN'],
        'modified volleyball women' => ['MODIFIED-VOLLEYBALL-WOMEN'],
        'modified voleyball' => ['MODIFIED-VOLLEYBALL'],
        'pencil drawing' => ['CULT-PENCIL-DRAWING'],
        'run' => ['RUNS'],
        'run 100m' => ['100M-RUN'],
        'run 200m' => ['200M-RUN'],
        'run 400 mm dash' => ['400M-RUN'],
        'run 400m' => ['400M-RUN'],
        'relay 4x100' => ['RELAY-4X100'],
        'relay 4x400' => ['RELAY-4X400'],
        '400m dash' => ['400M-RUN'],
        'runs' => ['RUNS'],
        'runs 100mm dash' => ['100M-RUN'],
        'run festival dancer' => ['RUNS', 'CULT-FEST-DANCE'],
        'russian' => ['RUSSIAN-SOFTBALL'],
        'russian soft ball' => ['RUSSIAN-SOFTBALL'],
        'russian softball' => ['RUSSIAN-SOFTBALL'],
        'shot put' => ['SHOT-PUT'],
        'shotput' => ['SHOT-PUT'],
        'short film' => ['CULT-SHORT-FILM'],
        'shortfilm' => ['CULT-SHORT-FILM'],
        'modern balitaw' => ['CULT-MASHUP-BALITAW'],
        'sayawerte' => ['CULT-SAYAWERTE-DANCE'],
        'sing swerte' => ['CULT-SING-SOLO'],
        'singswerte' => ['CULT-SING-SOLO'],
        'singswerte duet' => ['CULT-SING-DUET'],
        'singwerte duet' => ['CULT-SING-DUET'],
        'singswerte solo' => ['CULT-SING-SOLO'],
        'table tennis' => ['TABLE-TENNIS'],
        'table tennis men' => ['TABLE-TENNIS-SINGLES-MEN'],
        'table tennis women' => ['TABLE-TENNIS-SINGLES-WOMEN'],
        'table tennis double men' => ['TABLE-TENNIS-DOUBLES-MEN'],
        'table tennis double women' => ['TABLE-TENNIS-DOUBLES-WOMEN'],
        'table tennis doubles men' => ['TABLE-TENNIS-DOUBLES-MEN'],
        'table tennis doubles women' => ['TABLE-TENNIS-DOUBLES-WOMEN'],
        'table tennis single men' => ['TABLE-TENNIS-SINGLES-MEN'],
        'table tennis single women' => ['TABLE-TENNIS-SINGLES-WOMEN'],
        'table tennis singles' => ['TABLE-TENNIS-SINGLES'],
        'table tennis singles men' => ['TABLE-TENNIS-SINGLES-MEN'],
        'table tennis singles women' => ['TABLE-TENNIS-SINGLES-WOMEN'],
        'throws javelin' => ['JAVELIN-THROW'],
        'throws shotput' => ['SHOT-PUT'],
        'tikarol creare' => [],
        'tikarol' => [],
        'treasurehenyo' => ['CULT-TREASURE'],
        'triple jump' => ['TRIPLE-JUMP'],
        'volleyball' => ['VOLLEYBALL'],
        'volleyball 6x6' => ['VOLLEYBALL'],
        'volleyball men' => ['VOLLEYBALL-MEN'],
        'volleyball women' => ['VOLLEYBALL-WOMEN'],
        'mlbb girls' => ['ESPORT-ML-WOMEN'],
        'mlbb men' => ['ESPORT-ML-MEN'],
        'and pageant' => ['CULT-STUDENT-PAGEANT'],
        'women coach' => [],
        '6x6 volleyball' => ['VOLLEYBALL'],
        'table tennis doubles' => ['TABLE-TENNIS-DOUBLES'],
        'treasure hunt' => ['CULT-TREASURE'],
        'vocal solo' => ['CULT-SING-SOLO'],
        'festival dancer short film' => ['CULT-FEST-DANCE', 'CULT-SHORT-FILM'],
        'food com.' => [],
        'food comm' => [],
        'tikarol creae' => [],
        'visual arts' => ['CULT-DIGITAL-POSTER'],
    ];

    abstract protected function teamCode(): string;

    abstract protected function rosterFile(): string;

    protected function pairKey(string $name, string $sportCode): ?string
    {
        return null;
    }

    protected function fillsMissingFieldsWithNa(): bool
    {
        return false;
    }

    public function run(bool $newPlayersOnly = false): void
    {
        $edition = $this->defaultEdition();
        $team = Team::query()
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->where('code', $this->teamCode())
            ->firstOrFail();
        $assignedBy = User::query()->where('role', 'admin')->value('id');
        $editionSports = EditionSport::query()
            ->with('sport')
            ->where('edition_id', $edition->id)
            ->get()
            ->keyBy(fn (EditionSport $editionSport): string => $editionSport->sport?->code ?? '');
        $roster = $this->roster();
        $expectedAssignments = [];
        $importedRows = [];
        $created = 0;
        $registrations = 0;

        DB::transaction(function () use ($edition, $team, $assignedBy, $editionSports, $roster, $newPlayersOnly, &$expectedAssignments, &$importedRows, &$created, &$registrations): void {
            foreach ($roster as $row) {
                // The additions import leaves existing people, including archived
                // records and their manually edited registrations, untouched.
                if ($newPlayersOnly && $this->findStudent($row, mergeDuplicates: false) !== null) {
                    continue;
                }

                $student = $this->student($row, $edition, $team, $created);
                $existingMembership = TeamMember::query()
                    ->where('edition_id', $edition->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($existingMembership !== null && $existingMembership->team_id !== $team->id) {
                    throw new RuntimeException($student->full_name.' is already assigned to a different team in this edition.');
                }

                TeamMember::query()->firstOrCreate(
                    ['edition_id' => $edition->id, 'student_id' => $student->id],
                    [
                        'team_id' => $team->id,
                        'assigned_by' => $assignedBy,
                        'assigned_at' => now(),
                    ],
                );

                $expectedAssignments[$student->id] = array_fill_keys($row['sports'], true);
                $importedRows[] = $row;
            }

            if (! $newPlayersOnly) {
                $this->removeOutdatedAssignments($edition, $team, $expectedAssignments);
            }

            $eligibility = app(EligibilityService::class);
            foreach ($importedRows as $row) {
                $student = $this->findStudent($row);

                foreach ($row['sports'] as $sportCode) {
                    $editionSport = $editionSports->get($sportCode);
                    if ($editionSport === null) {
                        throw new RuntimeException('Sport '.$sportCode.' is not configured for '.$edition->name.'.');
                    }
                    if ($editionSport->status === 'archived' || $editionSport->sport?->status !== 'active' || ! $editionSport->sport->acceptsStudentGender($student->gender)) {
                        throw new RuntimeException('Sport '.$sportCode.' is unavailable or does not match '.$student->full_name.'\'s gender.');
                    }

                    $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);
                    $entry = AthleteEntry::query()->firstOrCreate(
                        [
                            'edition_sport_id' => $editionSport->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'team_id' => $requiresTeam ? $team->id : null,
                            'pair_key' => $this->pairKey($row['name'], $sportCode),
                            'status' => 'active',
                            'medical_certificate_status' => $eligibility->requiresMedicalCertificate($editionSport) ? 'pending' : 'not_required',
                            'assigned_by' => $assignedBy,
                            'assigned_at' => now(),
                        ],
                    );
                    $entry->fill([
                        'team_id' => $requiresTeam ? $team->id : null,
                        'pair_key' => $this->pairKey($row['name'], $sportCode) ?? $entry->pair_key,
                        'status' => 'active',
                    ]);
                    if ($entry->isDirty()) {
                        $entry->save();
                    }

                    $registrations++;
                }
            }
        });

        $this->command?->info($team->name.' roster seeded: '.$created.' new students and '.$registrations.' sport registrations.');
    }

    private function defaultEdition(): IntramuralEdition
    {
        $default = config('intramurals.default_edition');

        return IntramuralEdition::query()
            ->where('name', $default['name'] ?? 'SLSUBC INTRAMURALS 2026')
            ->where('school_year', $default['school_year'] ?? '2026-2027')
            ->first()
            ?? IntramuralEdition::query()->where('status', 'active')->latest('starts_on')->firstOrFail();
    }

    /**
     * @return array<int, array{name: string, gender: string, year_level: string, section: ?string, sports: array<int, string>}>
     */
    private function roster(): array
    {
        $path = __DIR__.'/rosters/'.$this->rosterFile().'.json';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read roster data at '.$path.'.');
        }

        $rows = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new RuntimeException('Roster data must be a JSON array: '.$path);
        }

        $roster = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['name'] ?? null)) {
                throw new RuntimeException('Roster data contains an invalid row: '.$path);
            }

            $fillsMissingFieldsWithNa = $this->fillsMissingFieldsWithNa();
            foreach (['gender', 'year_section', 'sports'] as $field) {
                if (isset($row[$field]) && ! is_string($row[$field])) {
                    throw new RuntimeException('Roster data contains an invalid '.$field.' for '.$row['name'].'.');
                }
                if (! $fillsMissingFieldsWithNa && ! is_string($row[$field] ?? null)) {
                    throw new RuntimeException('Roster data contains an invalid row: '.$path);
                }
            }

            $name = $this->canonicalName($row['name']);
            $genderValue = trim($row['gender'] ?? '');
            $gender = $genderValue === '' && $fillsMissingFieldsWithNa
                ? 'N/A'
                : ucfirst(Str::lower($genderValue));
            if (! in_array($gender, ['Male', 'Female', 'N/A'], true)) {
                throw new RuntimeException('Invalid gender for '.$name.': '.$genderValue);
            }

            $yearSection = trim($row['year_section'] ?? '');
            if ($yearSection === '' && $fillsMissingFieldsWithNa) {
                $yearSection = 'N/A';
            }
            $sportsValue = trim($row['sports'] ?? '');
            if ($sportsValue === '' && $fillsMissingFieldsWithNa) {
                $sportsValue = 'N/A';
            }

            $academicDetails = $this->academicDetails($yearSection, $name, $fillsMissingFieldsWithNa);
            if (array_key_exists('sport_codes', $row)) {
                if (! is_array($row['sport_codes']) || ! array_is_list($row['sport_codes']) || collect($row['sport_codes'])->contains(fn ($code) => ! is_string($code) || preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $code) !== 1 || str_starts_with($code, 'CULT-'))) {
                    throw new RuntimeException('Invalid sport_codes for '.$name.'.');
                }
                $sports = array_values(array_unique($row['sport_codes']));
            } else {
                $sports = $this->sportCodes($sportsValue, $name, $gender);
            }
            $aliases = $row['aliases'] ?? [];
            if (! is_array($aliases) || ! array_is_list($aliases) || collect($aliases)->contains(fn ($alias) => ! is_string($alias) || trim($alias) === '')) {
                throw new RuntimeException('Invalid name aliases for '.$name.'.');
            }
            $nameKey = $this->normalizeName($name);

            if (isset($roster[$nameKey])) {
                $existing = $roster[$nameKey];
                if ($existing['gender'] !== $gender || $existing['year_level'] !== $academicDetails['year_level'] || $existing['section'] !== $academicDetails['section']) {
                    throw new RuntimeException('Conflicting duplicate roster rows for '.$name.'.');
                }

                $roster[$nameKey]['sports'] = array_values(array_unique(array_merge($existing['sports'], $sports)));
                $roster[$nameKey]['aliases'] = array_values(array_unique(array_merge($existing['aliases'], $aliases)));

                continue;
            }

            $roster[$nameKey] = [
                'name' => $name,
                'gender' => $gender,
                'year_level' => $academicDetails['year_level'],
                'section' => $academicDetails['section'],
                'sports' => $sports,
                'aliases' => $aliases,
            ];
        }

        return array_values($roster);
    }

    /** @return array{year_level: string, section: ?string} */
    private function academicDetails(string $value, string $name, bool $fillsMissingFieldsWithNa): array
    {
        $value = trim($value);
        if ($fillsMissingFieldsWithNa && ($value === '' || Str::upper($value) === 'N/A')) {
            $yearLevel = 'N/A';
            $section = 'N/A';
        } elseif ($fillsMissingFieldsWithNa && preg_match('/^([A-D])$/i', $value, $matches) === 1) {
            $yearLevel = 'N/A';
            $section = strtoupper($matches[1]);
        } elseif (preg_match('/([1-4])\s*[-\/]?\s*([A-D])\s*$/i', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $section = strtoupper($matches[2]);
            $yearLevel = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$year];
        } elseif (preg_match('/([1-4])\s*$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $section = $fillsMissingFieldsWithNa ? 'N/A' : null;
            $yearLevel = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$year];
        } else {
            throw new RuntimeException('Unable to parse year/section "'.$value.'" for '.$name.'.');
        }

        return [
            'year_level' => $yearLevel,
            'section' => $section,
        ];
    }

    /** @return array<int, string> */
    private function sportCodes(string $value, string $name, string $gender): array
    {
        if (trim($value) === '' || Str::upper(trim($value)) === 'N/A') {
            return [];
        }

        $labels = preg_split('/\s*(?:,|&|\+|\/)\s*|\s+(?:and)\s+|\s+-\s+/iu', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if ($labels === false || $labels === []) {
            throw new RuntimeException('No sports listed for '.$name.'.');
        }

        $codes = [];
        foreach ($labels as $label) {
            $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $label) ?? ''));
            if (! isset(self::SPORT_ALIASES[$key])) {
                throw new RuntimeException('Unrecognized sport "'.$label.'" for '.$name.'.');
            }

            foreach (self::SPORT_ALIASES[$key] as $code) {
                $codes[] = match ($code) {
                    'VOLLEYBALL' => $this->genderedSportCode('VOLLEYBALL-MEN', 'VOLLEYBALL-WOMEN', $gender, $name),
                    'MODIFIED-VOLLEYBALL' => $this->genderedSportCode('MODIFIED-VOLLEYBALL-MEN', 'MODIFIED-VOLLEYBALL-WOMEN', $gender, $name),
                    'CHESS' => $this->genderedSportCode('CHESS-MEN', 'CHESS-WOMEN', $gender, $name),
                    'ESPORT-ML' => $this->genderedSportCode('ESPORT-ML-MEN', 'ESPORT-ML-WOMEN', $gender, $name),
                    'BASKET-3X3' => $this->genderedSportCode('BASKET-3X3-MEN', 'BASKET-3X3-WOMEN', $gender, $name),
                    'BASKET-5X5' => $this->genderedSportCode('BASKET-5X5-MEN', 'BASKET-5X5-WOMEN', $gender, $name),
                    'BEACH-VOLLEYBALL' => $this->genderedSportCode('BEACH-VOLLEYBALL-MEN', 'BEACH-VOLLEYBALL-WOMEN', $gender, $name),
                    'BADMINTON' => $this->genderedSportCode('BADMINTON-DOUBLES-MEN', 'BADMINTON-DOUBLES-WOMEN', $gender, $name),
                    'BADMINTON-TEAM' => $this->genderedSportCode('BADMINTON-TEAM-MEN', 'BADMINTON-TEAM-WOMEN', $gender, $name),
                    'BADMINTON-DOUBLES' => $this->genderedSportCode('BADMINTON-DOUBLES-MEN', 'BADMINTON-DOUBLES-WOMEN', $gender, $name),
                    'BADMINTON-SINGLES' => $this->genderedSportCode('BADMINTON-SINGLES-MEN', 'BADMINTON-SINGLES-WOMEN', $gender, $name),
                    'TABLE-TENNIS' => $this->genderedSportCode('TABLE-TENNIS-SINGLES-MEN', 'TABLE-TENNIS-SINGLES-WOMEN', $gender, $name),
                    'TABLE-TENNIS-TEAM' => $this->genderedSportCode('TABLE-TENNIS-TEAM-MEN', 'TABLE-TENNIS-TEAM-WOMEN', $gender, $name),
                    'TABLE-TENNIS-DOUBLES' => $this->genderedSportCode('TABLE-TENNIS-DOUBLES-MEN', 'TABLE-TENNIS-DOUBLES-WOMEN', $gender, $name),
                    'TABLE-TENNIS-SINGLES' => $this->genderedSportCode('TABLE-TENNIS-SINGLES-MEN', 'TABLE-TENNIS-SINGLES-WOMEN', $gender, $name),
                    default => in_array($code, ['RUNS', '100M-RUN', '200M-RUN', '400M-RUN', 'RELAY-4X100', 'RELAY-4X400', 'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW', 'JUMP', 'LONG-JUMP', 'TRIPLE-JUMP', 'HIGH-JUMP'], true)
                        ? $this->genderedSportCode($code.'-MEN', $code.'-WOMEN', $gender, $name)
                        : $code,
                };
            }
        }

        return array_values(array_unique($codes));
    }

    private function genderedSportCode(string $maleCode, string $femaleCode, string $gender, string $name): string
    {
        if (! in_array($gender, ['Male', 'Female'], true)) {
            throw new RuntimeException('A valid gender is required to assign gender-specific sports to '.$name.'.');
        }

        return $gender === 'Male' ? $maleCode : $femaleCode;
    }

    private function canonicalName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if (str_contains($name, ',')) {
            [$familyName, $givenNames] = array_map('trim', array_pad(explode(',', $name, 2), 2, ''));
            if ($familyName !== '' && $givenNames !== '') {
                $name = $givenNames.' '.$familyName;
            }
        }

        $name = preg_replace('/(?<!\pL)(\pL)\.(?=\pL)/u', '$1. ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function normalizeName(string $name): string
    {
        $normalized = Str::ascii(Str::lower($this->canonicalName($name)));
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    /** @param array{name: string, gender: string, year_level: string, section: ?string, sports: array<int, string>} $row */
    private function student(array $row, IntramuralEdition $edition, Team $team, int &$created): Student
    {
        $student = $this->findStudent($row);
        $nameParts = preg_split('/\s+/u', $row['name'], -1, PREG_SPLIT_NO_EMPTY);
        if ($nameParts === false || count($nameParts) < 2) {
            throw new RuntimeException('Unable to split student name: '.$row['name'].'.');
        }

        $lastName = array_pop($nameParts);
        $firstName = array_shift($nameParts);
        $middleName = $nameParts !== [] ? implode(' ', $nameParts) : null;
        $attributes = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'school_year' => $edition->school_year,
            'course_id' => $team->course_id,
            'gender' => $row['gender'],
            'year_level' => $row['year_level'],
            'section' => $row['section'],
            'status' => 'active',
        ];

        if ($student === null) {
            $student = new Student;
            $student->student_number = Str::upper(Str::slug($team->code.' '.$row['name']));
            $created++;
        } elseif ($student->trashed()) {
            $student->restore();
        }

        $student->fill($attributes);
        $student->save();

        return $student;
    }

    /** @param array{name: string, gender: string, year_level: string, section: ?string, sports: array<int, string>} $row */
    private function findStudent(array $row, bool $mergeDuplicates = true): ?Student
    {
        $nameParts = preg_split('/\s+/u', $row['name'], -1, PREG_SPLIT_NO_EMPTY);
        if ($nameParts === false || count($nameParts) < 2) {
            throw new RuntimeException('Unable to split student name: '.$row['name'].'.');
        }

        $names = [$row['name'], ...($row['aliases'] ?? [])];
        $lastNames = array_map(fn ($name) => last(preg_split('/\s+/u', $this->canonicalName($name), -1, PREG_SPLIT_NO_EMPTY)), $names);
        $nameKeys = array_map(fn ($name) => $this->normalizeName($name), $names);
        $candidates = Student::withTrashed()
            ->where(function ($query) use ($lastNames): void {
                foreach ($lastNames as $lastName) {
                    $query->orWhere('last_name', $lastName)->orWhere('last_name', 'like', '% '.$lastName);
                }
            })
            ->get();
        $matches = $candidates->filter(fn (Student $student): bool => in_array($this->normalizeName($student->full_name), $nameKeys, true));
        $active = $matches->reject(fn (Student $student): bool => $student->trashed())
            ->sortBy(fn (Student $student): int => $this->normalizeName($student->full_name) === $nameKeys[0] ? 0 : 1);

        if ($active->isNotEmpty()) {
            $student = $active->first();
            if ($mergeDuplicates) {
                foreach ($active->skip(1) as $duplicate) {
                    $this->mergeDuplicateStudent($student, $duplicate);
                }
            }

            return $student;
        }

        return $matches->first();
    }

    private function mergeDuplicateStudent(Student $student, Student $duplicate): void
    {
        foreach ($duplicate->teamMembers()->get() as $membership) {
            $existing = $student->teamMembers()->where('edition_id', $membership->edition_id)->first();
            if ($existing && $existing->team_id !== $membership->team_id) {
                throw new RuntimeException('Duplicate '.$duplicate->full_name.' belongs to another faction; resolve the faction before importing.');
            }
            $existing ? $membership->delete() : $membership->update(['student_id' => $student->id]);
        }
        foreach ($duplicate->athleteEntries()->get() as $entry) {
            $existing = $student->athleteEntries()->where('edition_sport_id', $entry->edition_sport_id)->first();
            if ($existing === null) {
                $entry->update(['student_id' => $student->id]);

                continue;
            }
            if ($existing->team_id !== $entry->team_id || (filled($existing->pair_key) && filled($entry->pair_key) && $existing->pair_key !== $entry->pair_key)) {
                throw new RuntimeException('Duplicate registration for '.$duplicate->full_name.' has conflicting faction or pair assignments.');
            }
            $competitors = BracketCompetitor::whereJsonContains('athlete_entry_ids', $entry->id)->get();
            // Team competitors keep their identity when their roster is
            // deduplicated. Individual/pair match history needs manual review.
            if (DB::table('schedule_participants')->where('athlete_entry_id', $entry->id)->exists()
                || $competitors->contains(fn ($competitor) => $competitor->type !== 'team')) {
                throw new RuntimeException('Duplicate registration for '.$duplicate->full_name.' is used in a schedule or bracket; merge it before importing.');
            }
            foreach ($competitors as $competitor) {
                $competitor->update(['athlete_entry_ids' => collect($competitor->athlete_entry_ids)->map(fn ($id) => (int) $id === $entry->id ? $existing->id : (int) $id)->unique()->values()->all()]);
            }
            if ($entry->medical_certificate_status === 'verified' && $existing->medical_certificate_status !== 'verified') {
                $existing->update($entry->only(['medical_certificate_status', 'medical_certificate_reviewed_by', 'medical_certificate_reviewed_at', 'medical_certificate_notes']));
            }
            $entry->delete();
        }
        $duplicate->delete();
    }

    /**
     * @param  array<int, array<string, bool>>  $expectedAssignments
     */
    private function removeOutdatedAssignments(IntramuralEdition $edition, Team $team, array $expectedAssignments): void
    {
        $expectedStudentIds = array_map('intval', array_keys($expectedAssignments));
        $staleMemberships = TeamMember::query()
            ->where('edition_id', $edition->id)
            ->where('team_id', $team->id)
            ->whereNotIn('student_id', $expectedStudentIds)
            ->whereDoesntHave('student.athleteEntries', fn ($query) => $query
                ->whereHas('editionSport', fn ($configuration) => $configuration->where('edition_id', $edition->id)
                    ->whereHas('sport', fn ($sport) => $sport->where('code', 'like', 'CULT-%'))))
            ->get(['id', 'student_id']);
        $managedStudentIds = array_values(array_unique(array_merge(
            $expectedStudentIds,
            $staleMemberships->pluck('student_id')->map(fn ($id): int => (int) $id)->all(),
        )));

        $staleEntryIds = AthleteEntry::query()
            ->whereIn('student_id', $managedStudentIds)
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->with('editionSport.sport')
            ->get()
            ->filter(function (AthleteEntry $entry) use ($expectedAssignments): bool {
                $sportCode = $entry->editionSport?->sport?->code;

                if ($sportCode !== null && str_starts_with($sportCode, 'CULT-')) {
                    return false;
                }

                return $sportCode === null || ! isset($expectedAssignments[$entry->student_id][$sportCode]);
            })
            ->pluck('id')
            ->all();

        if ($staleEntryIds !== [] && Schema::hasTable('schedule_participants')) {
            $hasScheduledEntries = DB::table('schedule_participants')
                ->whereIn('athlete_entry_id', $staleEntryIds)
                ->exists();

            if ($hasScheduledEntries) {
                throw new RuntimeException('Cannot replace the '.$team->name.' roster because outdated athlete registrations are already used in schedules.');
            }
        }

        if ($staleEntryIds !== []) {
            AthleteEntry::query()->whereIn('id', $staleEntryIds)->delete();
        }

        if ($staleMemberships->isNotEmpty()) {
            TeamMember::query()->whereIn('id', $staleMemberships->pluck('id'))->delete();
        }
    }
}
