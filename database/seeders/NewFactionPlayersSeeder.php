<?php

namespace Database\Seeders;

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EligibilityService;
use Database\Seeders\Concerns\SkipsProductionRosterImports;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Add missing source players without replacing existing roster edits or results. */
class NewFactionPlayersSeeder extends Seeder
{
    use SkipsProductionRosterImports;

    public function run(): void
    {
        if ($this->skipsProductionRosterImport()) {
            return;
        }

        $studentsBefore = Student::count();
        $entriesBefore = AthleteEntry::count();
        try {
            DB::transaction(function (): void {
                $this->callWith([
                    SeaDragonsRosterSeeder::class,
                    TerraquaticEaglesRosterSeeder::class,
                    TrojanWarriorsRosterSeeder::class,
                ], ['newPlayersOnly' => true]);
                $this->addSourceRegistrations();
            });
            app(AuditService::class)->record('roster.additions_imported', newValues: [
                'new_players' => Student::count() - $studentsBefore,
                'new_registrations' => AthleteEntry::count() - $entriesBefore,
                'mode' => 'Additions only; existing records and results preserved',
            ]);
        } catch (Throwable $exception) {
            app(AuditService::class)->record('roster.additions_import_failed', newValues: ['error_type' => $exception::class], outcome: 'failed');
            throw $exception;
        }
    }

    private function addSourceRegistrations(): void
    {
        $updates = json_decode(file_get_contents(__DIR__.'/rosters/registration_additions.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($updates as $update) {
            $team = Team::where('code', $update['team_code'])->where('status', 'active')
                ->whereHas('edition', fn ($query) => $query->where('name', config('intramurals.default_edition.name'))->where('school_year', config('intramurals.default_edition.school_year')))
                ->firstOrFail();
            $student = Student::where('status', 'active')->whereHas('teamMembers', fn ($query) => $query->where('team_id', $team->id))
                ->get()->first(fn (Student $student) => $student->student_number === $update['student_number'] || $student->full_name === $update['name']);
            if ($student === null) {
                $this->command?->warn('Skipped source update for missing or inactive player '.$update['name'].'.');

                continue;
            }
            foreach ($update['sport_codes'] as $code) {
                $event = EditionSport::with('sport')->where('edition_id', $team->edition_id)
                    ->whereHas('sport', fn ($query) => $query->where('code', $code))->firstOrFail();
                if ($event->status === 'archived' || $event->sport->status !== 'active' || ! $event->sport->acceptsStudentGender($student->gender)) {
                    throw new RuntimeException('Source registration '.$code.' is unavailable for '.$student->full_name.'.');
                }
                $entry = AthleteEntry::firstOrCreate(['edition_sport_id' => $event->id, 'student_id' => $student->id], [
                    'team_id' => in_array($event->participant_type, ['team', 'dual'], true) ? $team->id : null,
                    'status' => 'active',
                    'medical_certificate_status' => app(EligibilityService::class)->requiresMedicalCertificate($event) ? 'pending' : 'not_required',
                    'assigned_by' => auth()->id() ?? User::where('role', 'admin')->value('id'),
                    'assigned_at' => now(),
                ]);
                if ($entry->wasRecentlyCreated) {
                    app(AuditService::class)->record('roster.registration_added', $entry, newValues: [
                        'player' => $student->full_name,
                        'sport' => $event->sport->name,
                        'source_row' => $update['source_row'],
                        'snapshot_date' => $update['snapshot_date'],
                    ]);
                }
            }
        }
    }
}
