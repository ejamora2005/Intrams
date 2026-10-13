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
use Illuminate\Support\Str;

/**
 * Seeds the supplied Trojan Warriors roster and its sport registrations.
 *
 * Student IDs were not included in the supplied list, so new records use
 * deterministic TROJAN- identifiers. Existing students are matched by name
 * first, which keeps this seeder safe to run alongside the original roster.
 */
class TrojanWarriorsRosterSeeder extends Seeder
{
    public function run(): void
    {
        $edition = $this->defaultEdition();
        $team = $this->defaultTeam($edition);
        $assignedBy = User::query()->where('role', 'admin')->value('id');
        $editionSports = EditionSport::query()
            ->with('sport')
            ->where('edition_id', $edition->id)
            ->get()
            ->keyBy(fn (EditionSport $editionSport): string => $editionSport->sport?->code ?? '');
        $eligibility = app(EligibilityService::class);
        $created = 0;
        $registrations = 0;

        DB::transaction(function () use ($edition, $team, $assignedBy, $editionSports, $eligibility, &$created, &$registrations): void {
            foreach ($this->roster() as $row) {
                $student = $this->student($row, $edition, $team, $created);

                $membership = TeamMember::query()->firstOrCreate(
                    ['edition_id' => $edition->id, 'student_id' => $student->id],
                    [
                        'team_id' => $team->id,
                        'assigned_by' => $assignedBy,
                        'assigned_at' => now(),
                    ],
                );

                foreach ($row['sports'] as $sportAssignment) {
                    $assignment = is_string($sportAssignment)
                        ? ['code' => $sportAssignment]
                        : $sportAssignment;
                    $editionSport = $editionSports->get($assignment['code']);

                    if (! $editionSport) {
                        $this->command?->warn('Skipped '.$student->full_name.' / '.$assignment['code'].' because the sport is not configured.');
                        continue;
                    }

                    $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);

                    AthleteEntry::query()->firstOrCreate(
                        [
                            'edition_sport_id' => $editionSport->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'team_id' => $requiresTeam ? $membership->team_id : null,
                            'pair_key' => $assignment['pair_key'] ?? null,
                            'status' => 'active',
                            'medical_certificate_status' => $eligibility->requiresMedicalCertificate($editionSport) ? 'pending' : 'not_required',
                            'assigned_by' => $assignedBy,
                            'assigned_at' => now(),
                        ],
                    );
                    $registrations++;
                }
            }
        });

        $this->command?->info("Trojan Warriors roster seeded: {$created} new students and {$registrations} sport registrations.");
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

    private function defaultTeam(IntramuralEdition $edition): Team
    {
        return Team::query()
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->where('code', 'trojan-warriors')
            ->firstOrFail();
    }

    /** @param array{first_name: string, middle_name?: string|null, last_name: string, gender: string, sports: array<int, string|array{code: string, pair_key?: string}>} $row */
    private function student(array $row, IntramuralEdition $edition, Team $team, int &$created): Student
    {
        $nameQuery = Student::withTrashed()
            ->where('first_name', $row['first_name'])
            ->where('last_name', $row['last_name']);
        $student = $nameQuery->first();

        if ($student === null) {
            $fullName = collect([$row['first_name'], $row['middle_name'] ?? null, $row['last_name']])->filter()->implode(' ');
            $studentNumber = 'TROJAN-'.Str::upper(Str::slug($fullName));
            $student = Student::withTrashed()->firstOrCreate(
                ['student_number' => $studentNumber],
                [
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'] ?? null,
                    'last_name' => $row['last_name'],
                    'school_year' => $edition->school_year,
                    'course_id' => $team->course_id,
                    'gender' => $row['gender'],
                    'year_level' => '4th',
                    'section' => 'A',
                    'status' => 'active',
                ],
            );
            $created++;
        }

        if ($student->trashed()) {
            $student->restore();
        }

        return $student;
    }

    /** @return array<int, array{first_name: string, middle_name?: string|null, last_name: string, gender: string, sports: array<int, string|array{code: string, pair_key?: string}>}> */
    private function roster(): array
    {
        $rows = [];
        $index = [];
        $add = function (string $first, string $last, string $gender, array $sports, ?string $middle = null) use (&$rows, &$index): void {
            $key = Str::lower(implode('|', array_filter([$first, $middle, $last])));
            if (isset($index[$key])) {
                $position = $index[$key];
                $rows[$position]['sports'] = array_merge($rows[$position]['sports'], $sports);
                return;
            }

            $index[$key] = count($rows);
            $rows[] = compact('first', 'last', 'gender', 'sports', 'middle');
            $rows[array_key_last($rows)]['first_name'] = $rows[array_key_last($rows)]['first'];
            $rows[array_key_last($rows)]['last_name'] = $rows[array_key_last($rows)]['last'];
            $rows[array_key_last($rows)]['middle_name'] = $rows[array_key_last($rows)]['middle'];
            unset($rows[array_key_last($rows)]['first'], $rows[array_key_last($rows)]['last'], $rows[array_key_last($rows)]['middle']);
        };

        // Baseball (men)
        foreach ([
            ['Jay-R', 'Danas'], ['Romel', 'Pitong'], ['Dan Rev', 'Gitanes'], ['Arnold', 'Dikit'],
            ['Wyndel', 'Medina'], ['Jonathan', 'Layo'], ['Romsclifford', 'Curay Bodoy'], ['Miled', 'Lopez'],
            ['Noel Vincent', 'Maucesa Ellorimo'], ['John Jherick', 'Cabarubias'], ['Ryan', 'Baslot'], ['Jericho', 'Bisnar'],
        ] as [$first, $last]) {
            $add($first, $last, 'Male', ['BASEBALL']);
        }

        // Russian softball (women)
        foreach ([
            ['Akissah Beth', 'Apilan'], ['Angel Nicole', 'Bermiso'], ['Divine Rose G.', 'Gitanes'], ['Aileen', 'Mantilla'],
            ['Cristina', 'Tago-on'], ['Grace', 'Gula'], ['Cristina', 'Dublois'], ['Fatima', 'Tulaytay'],
            ['Clarice', 'Gumapi'], ['Lian', 'Abengoza'], ['Cherry Ann', 'Himo'],
        ] as [$first, $last]) {
            $add($first, $last, 'Female', ['RUSSIAN-SOFTBALL']);
        }

        foreach ([
            ['Anna Mae', 'Guzon'], ['Juris', 'Nuñez'], ['Annalou', 'Oclarit'], ['Rizaly', 'Arañez'], ['Ann', 'Costorio'],
            ['Marielle', 'Cañon'], ['Danicha', 'Gomez'], ['Christine', 'Alvero'], ['Mary Grace', 'Lamoste'],
            ['Maria Apple Rose Stella', 'Pintuyan'], ['Elisha Mae', 'Abande'], ['April Grace', 'Aton'],
        ] as [$first, $last]) {
            $add($first, $last, 'Female', ['VOLLEYBALL-WOMEN']);
        }

        foreach ([
            ['Dexter', 'Hermogino'], ['Luciano', 'Cellan', 'II'], ['Hidefumi', 'Asano'], ['Jericho', 'Kuizon'],
            ['James', 'Cabillada'], ['Allen Justine', 'Araniel'], ['Justine', 'Lamoste'], ['Kendra', 'Gamora'],
            ['Peabo', 'Andrade'], ['Lance', 'Añora'], ['Carl', 'Bersabal'],
        ] as $person) {
            $add($person[0], $person[1], 'Male', ['VOLLEYBALL-MEN'], $person[2] ?? null);
        }

        foreach ([['Anna Mae', 'Guzon'], ['Elisha Mae', 'Abande'], ['Juris', 'Nuñez']] as [$first, $last]) {
            $add($first, $last, 'Female', ['BEACH-VOLLEYBALL-WOMEN']);
        }
        foreach ([['Kent', 'Gomora'], ['Peabo', 'Andrade'], ['James', 'Cabillada']] as [$first, $last]) {
            $add($first, $last, 'Male', ['BEACH-VOLLEYBALL-MEN']);
        }

        foreach ([
            ['Kevin', 'Lozada'], ['Gerald', 'Singson'], ['John EJ', 'Bernales'], ['John Loyd', 'Diamada'], ['Apollo', 'Paderes'],
            ['Arhon', 'Gabronino'], ['Rogelniño', 'Inal', 'Mondido Fe'], ['Hidefumi', 'Asano'], ['Jay Mark', 'Banayag'],
            ['Gian', 'Calunsod'], ['Christian', 'Trimucha'], ['Dane Dale', 'Ruales'], ['Mart', 'Perez'], ['Ian Jay', 'Asumbrado'],
        ] as $person) {
            $add($person[0], $person[1], 'Male', ['BASKET-5X5'], $person[2] ?? null);
        }
        foreach ([['Russel', 'Aguas'], ['Archie', 'Juanico'], ['AJ', 'Araniel'], ['Alixies', 'Punay']] as [$first, $last]) {
            $add($first, $last, 'Male', ['BASKET-3X3']);
        }

        $add('Christian', 'Trimucha', 'Male', ['TABLE-TENNIS-SINGLES-MEN']);
        $add('Ryan', 'Baslot', 'Male', ['TABLE-TENNIS-SINGLES-MEN']);
        $add('Romel', 'Pitong', 'Male', [['code' => 'TABLE-TENNIS-DOUBLES-MEN', 'pair_key' => 'trojan-tt-men-doubles-1']]);
        $add('Earl Lorence', 'Maraña', 'Male', [['code' => 'TABLE-TENNIS-DOUBLES-MEN', 'pair_key' => 'trojan-tt-men-doubles-1']]);
        $add('Bea', 'Galares', 'Female', ['TABLE-TENNIS-SINGLES-WOMEN']);
        $add('Charlene', 'Makilang', 'Female', ['TABLE-TENNIS-SINGLES-WOMEN']);
        $add('Eva Mae', 'Cabilic', 'Female', [['code' => 'TABLE-TENNIS-DOUBLES-WOMEN', 'pair_key' => 'trojan-tt-women-doubles-1']]);
        $add('Joanna Mae', 'Maraña', 'Female', [['code' => 'TABLE-TENNIS-DOUBLES-WOMEN', 'pair_key' => 'trojan-tt-women-doubles-1']]);

        $add('Nico', 'Piloton', 'Male', ['SHOT-PUT', 'DISCUS-THROW']);
        $add('Danilo', 'Dumagat', 'Male', ['DISCUS-THROW', 'JAVELIN-THROW']);
        $add('Arhon', 'Gabronino', 'Male', ['DISCUS-THROW']);
        $add('Mary Ann', 'Diagbel', 'Female', ['DISCUS-THROW', 'JAVELIN-THROW']);
        $add('Jay Ann', 'Minor', 'Female', ['DISCUS-THROW']);
        $add('Grace', 'Tago-on', 'Female', ['SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW']);
        $add('Bryle', 'Castuya', 'Male', ['JAVELIN-THROW']);
        $add('James', 'Baes', 'Male', ['JAVELIN-THROW', 'LONG-JUMP']);
        $add('Arjay', 'Dalagan', 'Male', ['JAVELIN-THROW']);
        $add('Analy', 'Sermillas', 'Female', ['JAVELIN-THROW']);

        $add('Dan Rev', 'Gitanes', 'Male', ['100M-RUN', 'RELAY-4X100']);
        $add('Novan Ken', 'Reyes', 'Male', ['100M-RUN', 'RELAY-4X100']);
        $add('Ariel', 'Jamora', 'Male', ['100M-RUN']);
        $add('Rose Ann', 'Mangubat', 'Female', ['100M-RUN']);
        $add('John Russel', 'Dabatos', 'Male', ['200M-RUN', 'RELAY-4X100']);
        $add('Xymond Bean', 'Canoy', 'Male', ['200M-RUN']);
        $add('Alexander', 'Alonsagay', 'Male', ['200M-RUN', 'RELAY-4X100']);
        $add('Joel', 'Portugaliza', 'Male', ['400M-RUN', 'RELAY-4X400']);
        $add('Jamaica', 'Palima', 'Female', ['400M-RUN', 'RELAY-4X100']);
        $add('Divine Rose G.', 'Gitanes', 'Female', ['RELAY-4X100']);
        $add('Aileen', 'Mantilla', 'Female', ['RELAY-4X100']);
        $add('Angel Nicole', 'Bermiso', 'Female', ['RELAY-4X100']);

        $add('Jericho', 'Bisnar', 'Male', ['TRIPLE-JUMP', 'HIGH-JUMP', 'LONG-JUMP']);
        $add('Efmarjoy', 'Timkang', 'Female', ['LONG-JUMP']);

        foreach ([
            ['John Lloyd', 'Galo'], ['John Dominic T.', 'Bulacoy'], ['Ian', 'Adobas'], ['Cristof', 'Tan'],
            ['Jhon Kristian', 'Cundangan'], ['Mathew', 'Ilao'], ['Emmanuel', 'Gula'], ['Luigie R.', 'Supangan'],
        ] as $person) {
            $add($person[0], $person[1], 'Male', ['ESPORT-ML'], $person[2] ?? null);
        }
        foreach ([
            ['Jocarose L.', 'Dumuluan'], ['Kristine', 'Lapasanda'], ['Lovely', 'Regis'], ['Alyson Mae', 'Sanico'], ['Jelian Marice D.', 'Lebato'],
        ] as $person) {
            $add($person[0], $person[1], 'Female', ['ESPORT-ML'], $person[2] ?? null);
        }

        foreach ([
            ['Ronell', 'Morillo'], ['Jonh Rogiel Mades', 'Tumanda'], ['Jhon Kristian', 'Cundangan'], ['Jhon Loyd', 'Diamada'],
        ] as [$first, $last]) {
            $add($first, $last, 'Male', ['CHESS-MEN']);
        }
        foreach ([['Daisy', 'Orosia'], ['Jemimah Samuelle', 'Señora'], ['Hannah Mae', 'Orias'], ['Joana', 'Cabal']] as [$first, $last]) {
            $add($first, $last, 'Female', ['CHESS-WOMEN']);
        }

        return $rows;
    }
}
