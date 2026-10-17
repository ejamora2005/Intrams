<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AthleteEntryService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EligibilityService $eligibilityService,
    ) {}

    /** @return Collection<int, EditionSport> */
    public function selectableSportsFor(IntramuralEdition $edition, bool $includeDual = false): Collection
    {
        $slotOrder = [
            EligibilityService::SLOT_MAJOR => 1,
            EligibilityService::SLOT_MINOR => 2,
            EligibilityService::SLOT_INDIVIDUAL_DUAL => 3,
        ];

        return $edition->editionSports()
            ->with('sport')
            ->whereIn('status', ['preparation', 'active'])
            ->when(! $includeDual, fn ($query) => $query->where('participant_type', '!=', 'dual'))
            ->get()
            ->filter(fn (EditionSport $editionSport): bool => $editionSport->sport?->status === 'active'
                && ($includeDual || $editionSport->participant_type !== 'dual')
                && $this->eligibilityService->isParticipationSport($editionSport))
            ->each(function (EditionSport $editionSport): void {
                $editionSport->setAttribute('participation_slot', $this->eligibilityService->slotFor($editionSport));
                $editionSport->setAttribute('counts_toward_participation_limit', $this->eligibilityService->countsTowardParticipationLimit($editionSport));
                $editionSport->setAttribute('is_relay_event', $this->eligibilityService->isRelayEvent($editionSport));
                $editionSport->setAttribute('paired_partner_exempt_genders', $this->eligibilityService->pairedPartnerExemptGenders($editionSport));
            })
            ->sortBy(fn (EditionSport $editionSport): string => sprintf(
                '%d-%d-%s',
                $editionSport->is_relay_event ? 1 : 0,
                $slotOrder[$editionSport->participation_slot] ?? 9,
                $editionSport->sport?->name ?? ''
            ))
            ->values();
    }

    /**
     * Register a newly created student for the sports selected at creation time.
     *
     * Dual events are intentionally excluded because they must be registered with
     * both members of the pair from the sport participant-management page.
     *
     * @param  array<int, int|string>  $editionSportIds
     * @return Collection<int, AthleteEntry>
     *
     * @throws ValidationException
     */
    public function registerSelectedSports(Student $student, IntramuralEdition $edition, array $editionSportIds, ?Team $team = null): Collection
    {
        $ids = collect($editionSportIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        if ($student->status !== 'active') {
            throw ValidationException::withMessages([
                'edition_sport_ids' => 'Only active students can be registered for sports.',
            ]);
        }

        $editionSports = EditionSport::query()
            ->with('sport')
            ->where('edition_id', $edition->id)
            ->whereIn('id', $ids)
            ->get();

        if ($editionSports->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'edition_sport_ids' => 'Choose sports from the active intramurals edition.',
            ]);
        }

        // When a runner and a relay are selected together, create the running
        // entry first so the relay eligibility check can confirm the requirement.
        $editionSports = $editionSports
            ->sortBy(fn (EditionSport $editionSport): int => $this->eligibilityService->isRelayEvent($editionSport) ? 1 : 0)
            ->values();

        foreach ($editionSports as $editionSport) {
            if (! in_array($editionSport->status, ['preparation', 'active'], true)
                || $editionSport->participant_type === 'dual'
                || ! $this->eligibilityService->isParticipationSport($editionSport)) {
                throw ValidationException::withMessages([
                    'edition_sport_ids' => ($editionSport->sport?->name ?? 'This sport').' cannot be selected while adding one student. Dual events must be assigned with both partners from sport participant management.',
                ]);
            }

            if (in_array($editionSport->participant_type, ['team', 'dual'], true)
                && (! $team
                    || $team->status !== 'active'
                    || (int) $team->edition_id !== (int) $edition->id
                    || ! $student->teamMembers()
                        ->where('edition_id', $edition->id)
                        ->where('team_id', $team->id)
                        ->exists())) {
                throw ValidationException::withMessages([
                    'edition_sport_ids' => 'A student must belong to an active faction before joining a team sport.',
                ]);
            }
        }

        return $editionSports->map(function (EditionSport $editionSport) use ($student, $edition, $team): AthleteEntry {
            try {
                $this->eligibilityService->assertStudentsMatchSportCategory(collect([$student]), $editionSport->sport);
                $this->eligibilityService->assertStudentsCanJoin(collect([$student]), $edition, $editionSport);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    'edition_sport_ids' => collect($exception->errors())->flatten()->implode(' '),
                ]);
            }

            $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);
            $entry = AthleteEntry::query()->create([
                'edition_sport_id' => $editionSport->id,
                'student_id' => $student->id,
                'team_id' => $requiresTeam ? $team?->id : null,
                'status' => 'active',
                'medical_certificate_status' => $this->eligibilityService->requiresMedicalCertificate($editionSport) ? 'pending' : 'not_required',
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $this->auditService->record('athlete_entry.created', $entry, null, $entry->only($entry->getFillable()));

            return $entry;
        })->values();
    }

    /**
     * Replace the active sport registrations for one student in an edition.
     * Removed registrations are kept as inactive history.
     */
    public function syncSelectedSports(Student $student, IntramuralEdition $edition, array $editionSportIds, ?Team $team = null): Collection
    {
        $ids = collect($editionSportIds)->map(fn (mixed $id): int => (int) $id)->filter()->unique()->values();
        $selectedSports = EditionSport::query()->with('sport')->where('edition_id', $edition->id)->whereIn('id', $ids)->get();
        if ($selectedSports->count() !== $ids->count()) {
            throw ValidationException::withMessages(['edition_sport_ids' => 'Choose sports from the active intramurals edition.']);
        }
        $invalidSports = $selectedSports->filter(fn (EditionSport $editionSport): bool =>
            ! $editionSport->sport instanceof \App\Models\Sport
            || ! in_array($editionSport->status, ['preparation', 'active'], true)
            || ! $this->eligibilityService->isParticipationSport($editionSport)
        );
        if ($invalidSports->isNotEmpty()) {
            throw ValidationException::withMessages(['edition_sport_ids' => 'One or more selected sports are no longer available. Refresh the page and choose from the current list.']);
        }
        try {
            $this->assertStudentCanJoinCategories($student, $selectedSports);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['edition_sport_ids' => collect($exception->errors())->flatten()->implode(' ')]);
        }
        $current = AthleteEntry::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->get();

        $remove = $current->filter(fn (AthleteEntry $entry): bool => ! $ids->contains((int) $entry->edition_sport_id));
        if ($remove->isNotEmpty()) {
            $remove->each(fn (AthleteEntry $entry) => $entry->update(['status' => 'inactive']));
        }

        $existingIds = $current->reject(fn (AthleteEntry $entry): bool => $remove->contains('id', $entry->id))
            ->pluck('edition_sport_id')->map(fn ($id) => (int) $id);
        $addIds = $ids->reject(fn (int $id): bool => $existingIds->contains($id))->all();

        return $addIds === [] ? collect() : $this->registerSelectedSports($student, $edition, $addIds, $team);
    }

    private function assertStudentCanJoinCategories(Student $student, Collection $editionSports): void
    {
        foreach ($editionSports as $editionSport) {
            $this->eligibilityService->assertStudentsMatchSportCategory(collect([$student]), $editionSport->sport);
        }
    }
}
