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

class IntramuralsStudentRosterSeeder extends Seeder
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

        DB::transaction(function () use ($edition, $team, $assignedBy, $editionSports, $eligibility): void {
            foreach ($this->students() as $row) {
                $student = Student::withTrashed()->updateOrCreate(
                    ['student_number' => $row['student_number']],
                    [
                        'first_name' => $row['first_name'],
                        'middle_name' => $row['middle_name'] ?? null,
                        'last_name' => $row['last_name'],
                        'school_year' => $edition->school_year,
                        'course_id' => $team->course_id,
                        'gender' => $row['gender'],
                        'year_level' => null,
                        'section' => null,
                        'status' => 'active',
                    ],
                );

                if ($student->trashed()) {
                    $student->restore();
                }

                TeamMember::query()->updateOrCreate(
                    [
                        'edition_id' => $edition->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'team_id' => $team->id,
                        'assigned_by' => $assignedBy,
                        'assigned_at' => now(),
                    ],
                );

                foreach ($row['event_codes'] as $eventCode) {
                    /** @var EditionSport|null $editionSport */
                    $editionSport = $editionSports->get($eventCode);

                    if (! $editionSport) {
                        $this->command?->warn('Skipped '.$row['student_number'].' event '.$eventCode.' because it is not configured.');
                        continue;
                    }

                    $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);

                    AthleteEntry::query()->updateOrCreate(
                        [
                            'edition_sport_id' => $editionSport->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'team_id' => $requiresTeam ? $team->id : null,
                            'status' => 'active',
                            'medical_certificate_status' => $eligibility->requiresMedicalCertificate($editionSport) ? 'pending' : 'not_required',
                            'assigned_by' => $assignedBy,
                            'assigned_at' => now(),
                        ],
                    );
                }
            }
        });
    }

    private function defaultEdition(): IntramuralEdition
    {
        $defaultEdition = config('intramurals.default_edition');

        return IntramuralEdition::query()
            ->where('name', $defaultEdition['name'] ?? 'SLSUBC INTRAMURALS 2026')
            ->where('school_year', $defaultEdition['school_year'] ?? '2026-2027')
            ->first()
            ?? IntramuralEdition::query()->where('status', 'active')->latest('starts_on')->firstOrFail();
    }

    private function defaultTeam(IntramuralEdition $edition): Team
    {
        return Team::query()
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->where('code', 'trojan-warriors')
            ->first()
            ?? Team::query()
                ->where('edition_id', $edition->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->firstOrFail();
    }

    /**
     * @return array<int, array{
     *     student_number: string,
     *     first_name: string,
     *     middle_name?: string|null,
     *     last_name: string,
     *     gender: string,
     *     event_codes: array<int, string>
     * }>
     */
    private function students(): array
    {
        return [
            ['student_number' => '2310042-2', 'first_name' => 'Jaylynne Gayle', 'last_name' => 'Libodlibod', 'gender' => 'Female', 'event_codes' => []],
            ['student_number' => '2310007-2', 'first_name' => 'Karyl', 'last_name' => 'Gesto', 'gender' => 'Female', 'event_codes' => ['CULT-COLLAB-PAINT', 'RUSSIAN-SOFTBALL']],
            ['student_number' => '2310060-1', 'first_name' => 'Jonh Rogiel', 'last_name' => 'Tumanda', 'gender' => 'Male', 'event_codes' => ['CHESS']],
            ['student_number' => '2310016-2', 'first_name' => 'Anna Mae', 'last_name' => 'Guzon', 'gender' => 'Female', 'event_codes' => ['VOLLEYBALL', 'BEACH-VOLLEYBALL']],
            ['student_number' => '2310067-2', 'first_name' => 'April Grace', 'last_name' => 'Aton', 'gender' => 'Female', 'event_codes' => ['VOLLEYBALL', 'CULT-FEST-DANCE']],
            ['student_number' => '2310025-2', 'first_name' => 'Marrisa', 'last_name' => 'Ruales', 'gender' => 'Female', 'event_codes' => ['BASKET-5X5']],
            ['student_number' => '2310072-1', 'first_name' => 'Hermogino Dexter', 'last_name' => 'T.', 'gender' => 'Male', 'event_codes' => ['VOLLEYBALL']],
            ['student_number' => '2310106-2', 'first_name' => 'Elisha Mae', 'last_name' => 'Abande', 'gender' => 'Female', 'event_codes' => ['VOLLEYBALL', 'BEACH-VOLLEYBALL']],
            ['student_number' => '2310005-2', 'first_name' => 'Clarice', 'last_name' => 'Gumapi', 'gender' => 'Female', 'event_codes' => ['BASKET-3X3', 'SOFTBALL']],
            ['student_number' => '2310050-2', 'first_name' => 'Eva Mae', 'last_name' => 'Cabilic', 'gender' => 'Female', 'event_codes' => ['TABLE-TENNIS', 'RUSSIAN-SOFTBALL']],
            ['student_number' => '2310036-2', 'first_name' => 'Cherry Ann', 'last_name' => 'Himo', 'gender' => 'Female', 'event_codes' => ['CULT-FEST-DANCE']],
            ['student_number' => '2310002-2', 'first_name' => 'Akissah Beth', 'last_name' => 'Apilan', 'gender' => 'Female', 'event_codes' => ['RUSSIAN-SOFTBALL']],
            ['student_number' => '2310099-1', 'first_name' => 'Ronell', 'last_name' => 'Morillo', 'gender' => 'Male', 'event_codes' => ['CHESS']],
            ['student_number' => '2310038-2', 'first_name' => 'Grace', 'last_name' => 'Gula', 'gender' => 'Female', 'event_codes' => ['RUSSIAN-SOFTBALL']],
            ['student_number' => '2310045-2', 'first_name' => 'Cristina Marie', 'middle_name' => 'G.', 'last_name' => 'Dublois', 'gender' => 'Female', 'event_codes' => ['RUSSIAN-SOFTBALL']],
            ['student_number' => '2310018-1', 'first_name' => 'Danilo', 'middle_name' => 'M.', 'last_name' => 'Dumagat', 'gender' => 'Male', 'event_codes' => ['SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW']],
            ['student_number' => '2310047-2', 'first_name' => 'Cristina', 'middle_name' => 'A.', 'last_name' => 'Tago-on', 'gender' => 'Female', 'event_codes' => ['RUSSIAN-SOFTBALL']],
        ];
    }
}
