<?php

namespace Database\Seeders;

use App\Models\AthleteEntry;
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
        'badminton d' => ['BADMINTON-DOUBLES'],
        'badminton d men' => ['BADMINTON-DOUBLES-MEN'],
        'badminton doubles' => ['BADMINTON-DOUBLES'],
        'badminton s' => ['BADMINTON-SINGLES'],
        'badminton singles' => ['BADMINTON-SINGLES'],
        'badminton s men' => ['BADMINTON-SINGLES-MEN'],
        'badminton s women' => ['BADMINTON-SINGLES-WOMEN'],
        'basketball' => ['BASKET-5X5'],
        'basketball 3x3' => ['BASKET-3X3'],
        'basketball 3x3 men' => ['BASKET-3X3-MEN'],
        'basketball 3x3 women' => ['BASKET-3X3-WOMEN'],
        'basketball 5x5' => ['BASKET-5X5'],
        'basketball 5x5 men' => ['BASKET-5X5-MEN'],
        'basketball 5x5 women' => ['BASKET-5X5-WOMEN'],
        'basketball 5v5' => ['BASKET-5X5'],
        'baseball' => ['BASEBALL'],
        'beach volleyball' => ['BEACH-VOLLEYBALL'],
        'beach volleyball men' => ['BEACH-VOLLEYBALL-MEN'],
        'beach volleyball women' => ['BEACH-VOLLEYBALL-WOMEN'],
        'chess' => ['CHESS'],
        'chess men' => ['CHESS-MEN'],
        'chess women' => ['CHESS-WOMEN'],
        'discuss' => ['DISCUS-THROW'],
        'discuss throw' => ['DISCUS-THROW'],
        'discus throw' => ['DISCUS-THROW'],
        'e sport' => ['ESPORT-ML'],
        'e sport men' => ['ESPORT-ML-MEN'],
        'e sport women' => ['ESPORT-ML-WOMEN'],
        'esports' => ['ESPORT-ML'],
        'festival' => ['CULT-FEST-DANCE'],
        'high jump' => ['HIGH-JUMP'],
        'javelin' => ['JAVELIN-THROW'],
        'javelin throw' => ['JAVELIN-THROW'],
        'long jump' => ['LONG-JUMP'],
        'mobile legend' => ['ESPORT-ML'],
        'mobile legends men' => ['ESPORT-ML-MEN'],
        'mobile legends women' => ['ESPORT-ML-WOMEN'],
        'mobile legends' => ['ESPORT-ML'],
        'modified volleyball' => ['MODIFIED-VOLLEYBALL'],
        'modified volleyball men' => ['MODIFIED-VOLLEYBALL-MEN'],
        'modified volleyball women' => ['MODIFIED-VOLLEYBALL-WOMEN'],
        'modified voleyball' => ['MODIFIED-VOLLEYBALL'],
        'run' => ['RUNS'],
        'run 100m' => ['100M-RUN'],
        'run 200m' => ['200M-RUN'],
        'run 400 mm dash' => ['400M-RUN'],
        'run 400m' => ['400M-RUN'],
        'relay 4x100' => ['RELAY-4X100'],
        'relay 4x400' => ['RELAY-4X400'],
        'russian' => ['RUSSIAN-SOFTBALL'],
        'russian soft ball' => ['RUSSIAN-SOFTBALL'],
        'russian softball' => ['RUSSIAN-SOFTBALL'],
        'shot put' => ['SHOT-PUT'],
        'shotput' => ['SHOT-PUT'],
        'table tennis' => ['TABLE-TENNIS'],
        'table tennis double men' => ['TABLE-TENNIS-DOUBLES-MEN'],
        'table tennis double women' => ['TABLE-TENNIS-DOUBLES-WOMEN'],
        'table tennis doubles men' => ['TABLE-TENNIS-DOUBLES-MEN'],
        'table tennis doubles women' => ['TABLE-TENNIS-DOUBLES-WOMEN'],
        'table tennis single men' => ['TABLE-TENNIS-SINGLES-MEN'],
        'table tennis single women' => ['TABLE-TENNIS-SINGLES-WOMEN'],
        'table tennis singles' => ['TABLE-TENNIS-SINGLES'],
        'table tennis singles men' => ['TABLE-TENNIS-SINGLES-MEN'],
        'table tennis singles women' => ['TABLE-TENNIS-SINGLES-WOMEN'],
        'triple jump' => ['TRIPLE-JUMP'],
        'volleyball' => ['VOLLEYBALL'],
        'volleyball men' => ['VOLLEYBALL-MEN'],
        'volleyball women' => ['VOLLEYBALL-WOMEN'],
    ];

    abstract protected function teamCode(): string;

    abstract protected function rosterFile(): string;

    protected function pairKey(string $name, string $sportCode): ?string
    {
        return null;
    }

    public function run(): void
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
        $created = 0;
        $registrations = 0;

        DB::transaction(function () use ($edition, $team, $assignedBy, $editionSports, $roster, &$expectedAssignments, &$created, &$registrations): void {
            foreach ($roster as $row) {
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
            }

            $this->removeOutdatedAssignments($edition, $team, $expectedAssignments);

            $eligibility = app(EligibilityService::class);
            foreach ($roster as $row) {
                $student = $this->findStudent($row);

                foreach ($row['sports'] as $sportCode) {
                    $editionSport = $editionSports->get($sportCode);
                    if ($editionSport === null) {
                        throw new RuntimeException('Sport '.$sportCode.' is not configured for '.$edition->name.'.');
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
                        'pair_key' => $this->pairKey($row['name'], $sportCode),
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
            if (! is_array($row) || ! is_string($row['name'] ?? null) || ! is_string($row['gender'] ?? null) || ! is_string($row['year_section'] ?? null) || ! is_string($row['sports'] ?? null)) {
                throw new RuntimeException('Roster data contains an invalid row: '.$path);
            }

            $name = $this->canonicalName($row['name']);
            $gender = ucfirst(Str::lower(trim($row['gender'])));
            if (! in_array($gender, ['Male', 'Female'], true)) {
                throw new RuntimeException('Invalid gender for '.$name.': '.$row['gender']);
            }

            $academicDetails = $this->academicDetails($row['year_section'], $name);
            $sports = $this->sportCodes($row['sports'], $name, $gender);
            $nameKey = $this->normalizeName($name);

            if (isset($roster[$nameKey])) {
                $existing = $roster[$nameKey];
                if ($existing['gender'] !== $gender || $existing['year_level'] !== $academicDetails['year_level'] || $existing['section'] !== $academicDetails['section']) {
                    throw new RuntimeException('Conflicting duplicate roster rows for '.$name.'.');
                }

                $roster[$nameKey]['sports'] = array_values(array_unique(array_merge($existing['sports'], $sports)));

                continue;
            }

            $roster[$nameKey] = [
                'name' => $name,
                'gender' => $gender,
                'year_level' => $academicDetails['year_level'],
                'section' => $academicDetails['section'],
                'sports' => $sports,
            ];
        }

        return array_values($roster);
    }

    /** @return array{year_level: string, section: ?string} */
    private function academicDetails(string $value, string $name): array
    {
        $value = trim($value);
        if (preg_match('/([1-4])\s*-?\s*([A-D])\s*$/i', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $section = strtoupper($matches[2]);
        } elseif (preg_match('/([1-4])\s*$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $section = null;
        } else {
            throw new RuntimeException('Unable to parse year/section "'.$value.'" for '.$name.'.');
        }

        return [
            'year_level' => [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$year],
            'section' => $section,
        ];
    }

    /** @return array<int, string> */
    private function sportCodes(string $value, string $name, string $gender): array
    {
        $labels = preg_split('/\s*(?:,|&)\s*/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
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
                    'VOLLEYBALL' => $gender === 'Male' ? 'VOLLEYBALL-MEN' : 'VOLLEYBALL-WOMEN',
                    'MODIFIED-VOLLEYBALL' => $gender === 'Male' ? 'MODIFIED-VOLLEYBALL-MEN' : 'MODIFIED-VOLLEYBALL-WOMEN',
                    'CHESS' => $gender === 'Male' ? 'CHESS-MEN' : 'CHESS-WOMEN',
                    'ESPORT-ML' => $gender === 'Male' ? 'ESPORT-ML-MEN' : 'ESPORT-ML-WOMEN',
                    'BASKET-3X3' => $gender === 'Male' ? 'BASKET-3X3-MEN' : 'BASKET-3X3-WOMEN',
                    'BASKET-5X5' => $gender === 'Male' ? 'BASKET-5X5-MEN' : 'BASKET-5X5-WOMEN',
                    'BEACH-VOLLEYBALL' => $gender === 'Male' ? 'BEACH-VOLLEYBALL-MEN' : 'BEACH-VOLLEYBALL-WOMEN',
                    'BADMINTON' => $gender === 'Male' ? 'BADMINTON-DOUBLES-MEN' : 'BADMINTON-DOUBLES-WOMEN',
                    'BADMINTON-TEAM' => $gender === 'Male' ? 'BADMINTON-TEAM-MEN' : 'BADMINTON-TEAM-WOMEN',
                    'BADMINTON-DOUBLES' => $gender === 'Male' ? 'BADMINTON-DOUBLES-MEN' : 'BADMINTON-DOUBLES-WOMEN',
                    'BADMINTON-SINGLES' => $gender === 'Male' ? 'BADMINTON-SINGLES-MEN' : 'BADMINTON-SINGLES-WOMEN',
                    'TABLE-TENNIS' => $gender === 'Male' ? 'TABLE-TENNIS-SINGLES-MEN' : 'TABLE-TENNIS-SINGLES-WOMEN',
                    'TABLE-TENNIS-TEAM' => $gender === 'Male' ? 'TABLE-TENNIS-TEAM-MEN' : 'TABLE-TENNIS-TEAM-WOMEN',
                    'TABLE-TENNIS-DOUBLES' => $gender === 'Male' ? 'TABLE-TENNIS-DOUBLES-MEN' : 'TABLE-TENNIS-DOUBLES-WOMEN',
                    'TABLE-TENNIS-SINGLES' => $gender === 'Male' ? 'TABLE-TENNIS-SINGLES-MEN' : 'TABLE-TENNIS-SINGLES-WOMEN',
                    default => in_array($code, ['RUNS', '100M-RUN', '200M-RUN', '400M-RUN', 'RELAY-4X100', 'RELAY-4X400', 'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW', 'JUMP', 'LONG-JUMP', 'TRIPLE-JUMP', 'HIGH-JUMP'], true)
                        ? $code.'-'.($gender === 'Male' ? 'MEN' : 'WOMEN')
                        : $code,
                };
            }
        }

        return array_values(array_unique($codes));
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
        $normalized = Str::lower($this->canonicalName($name));
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
            $attributes += [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
            ];
            $created++;
        } elseif ($student->trashed()) {
            $student->restore();
        }

        $student->fill($attributes);
        $student->save();

        return $student;
    }

    /** @param array{name: string, gender: string, year_level: string, section: ?string, sports: array<int, string>} $row */
    private function findStudent(array $row): ?Student
    {
        $nameParts = preg_split('/\s+/u', $row['name'], -1, PREG_SPLIT_NO_EMPTY);
        if ($nameParts === false || count($nameParts) < 2) {
            throw new RuntimeException('Unable to split student name: '.$row['name'].'.');
        }

        $lastName = end($nameParts);
        $candidates = Student::withTrashed()
            ->where(function ($query) use ($lastName): void {
                $query->where('last_name', $lastName)
                    ->orWhere('last_name', 'like', '% '.$lastName);
            })
            ->get();
        $matches = $candidates->filter(fn (Student $student): bool => $this->normalizeName($student->full_name) === $this->normalizeName($row['name']));

        if ($matches->count() > 1) {
            throw new RuntimeException('Multiple existing student records match '.$row['name'].'.');
        }

        return $matches->first();
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
