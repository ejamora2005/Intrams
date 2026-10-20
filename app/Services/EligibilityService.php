<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\ScheduleParticipant;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EligibilityService
{
    public const SLOT_MAJOR = 'major';

    public const SLOT_MINOR = 'minor';

    public const SLOT_INDIVIDUAL_DUAL = 'individual_or_dual';

    public const SLOT_OTHER = 'other';

    /** @return array<int, array<string, mixed>> */
    public function rulesForDisplay(): array
    {
        return config('intramurals.participation_rules.allowed_combinations', []);
    }

    public function maxEvents(): int
    {
        return (int) config('intramurals.participation_rules.max_events_per_student', 2);
    }

    public function individualDualOnlyMaxEvents(): int
    {
        return (int) config('intramurals.participation_rules.individual_dual_only_max_events', $this->maxEvents());
    }

    public function slotFor(EditionSport $editionSport): string
    {
        if ($this->isRelayEvent($editionSport)) {
            return self::SLOT_INDIVIDUAL_DUAL;
        }

        $pointSystem = $this->pointSystemKey($editionSport);

        return match ($pointSystem) {
            'sports_major' => self::SLOT_MAJOR,
            'sports_minor', 'sports_basketball_3x3' => self::SLOT_MINOR,
            'sports_athletics' => self::SLOT_INDIVIDUAL_DUAL,
            default => in_array($editionSport->participant_type, ['individual', 'dual'], true)
                ? self::SLOT_INDIVIDUAL_DUAL
                : self::SLOT_OTHER,
        };
    }

    public function slotLabel(string $slot): string
    {
        return match ($slot) {
            self::SLOT_MAJOR => 'Major',
            self::SLOT_MINOR => 'Minor',
            self::SLOT_INDIVIDUAL_DUAL => 'Individual/Dual',
            default => 'Other',
        };
    }

    /**
     * Determine whether an edition sport is governed by the student sport-selection rules.
     */
    public function isParticipationSport(EditionSport $editionSport): bool
    {
        return $this->countsForParticipation($editionSport)
            && in_array($this->slotFor($editionSport), [
                self::SLOT_MAJOR,
                self::SLOT_MINOR,
                self::SLOT_INDIVIDUAL_DUAL,
            ], true);
    }

    /**
     * Relay classification is independent of the configured scoring point system.
     */
    public function isRelayEvent(EditionSport $editionSport): bool
    {
        $editionSport->loadMissing('sport');

        return $editionSport->sport?->isRelay() === true
            || $this->sportCodeIsIn($editionSport, config('intramurals.participation_rules.relay_exempt_sport_codes', []));
    }

    public function countsTowardParticipationLimit(EditionSport $editionSport, ?Student $student = null): bool
    {
        return $this->isParticipationSport($editionSport)
            && (! $this->isRelayEvent($editionSport) || ! $student || ! $this->hasQualifyingRun($student, $editionSport->edition_id))
            && ! $this->isPairedPartnerExempt($editionSport, $student);
    }

    private function hasQualifyingRun(Student $student, int $editionId): bool
    {
        return $student->athleteEntries()->where('status', 'active')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $editionId)
                ->whereHas('sport', fn ($sportQuery) => $sportQuery->whereIn('code', config('intramurals.participation_rules.relay_qualifying_run_sport_codes', []))))
            ->exists();
    }

    /** @return array<int, string> */
    public function pairedPartnerExemptGenders(EditionSport $editionSport): array
    {
        $editionSport->loadMissing('sport');
        $sportCode = Str::upper((string) $editionSport->sport?->code);
        $configured = config('intramurals.participation_rules.paired_partner_exemptions', []);

        return collect($configured[$sportCode] ?? [])
            ->map(fn (mixed $gender): string => Str::lower((string) $gender))
            ->filter()
            ->values()
            ->all();
    }

    public function isPairedPartnerExempt(EditionSport $editionSport, ?Student $student): bool
    {
        if (! $student || ! filled($student->gender)) {
            return false;
        }

        return in_array(Str::lower((string) $student->gender), $this->pairedPartnerExemptGenders($editionSport), true);
    }

    public function requiresMedicalCertificate(EditionSport $editionSport): bool
    {
        $editionSport->loadMissing('sport');

        if (($editionSport->scoring_rules['non_scoring'] ?? false) === true) {
            return false;
        }

        $sportCode = Str::upper((string) $editionSport->sport?->code);
        $exemptCodes = collect(config('intramurals.medical_certificate.exempt_sport_codes', []))
            ->map(fn (string $code): string => Str::upper($code));

        if ($sportCode !== '' && $exemptCodes->contains($sportCode)) {
            return false;
        }

        return str_starts_with((string) $this->pointSystemKey($editionSport), 'sports_');
    }

    public function medicalCertificateStatusFor(AthleteEntry $entry): string
    {
        $entry->loadMissing('editionSport.sport');

        if (! $entry->editionSport || ! $this->requiresMedicalCertificate($entry->editionSport)) {
            return 'not_required';
        }

        $status = (string) ($entry->medical_certificate_status ?: 'pending');

        return $status === 'not_required' ? 'pending' : $status;
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, array<string, mixed>>
     */
    public function previewForStudents(Collection $students, IntramuralEdition $edition, EditionSport $additionalSport): Collection
    {
        return $students
            ->filter()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => $this->evaluateStudent($student, $edition, $additionalSport),
            ]);
    }

    /** @param Collection<int, Student> $students */
    public function registrationWarningForStudents(Collection $students, IntramuralEdition $edition): ?string
    {
        // Participation limits are advisory: keep registrations and surface the
        // same evaluation used by the Possible DQ watchlist after saving.
        $violations = $students
            ->map(fn (Student $student): array => $this->evaluateStudent($student, $edition))
            ->filter(fn (array $evaluation): bool => $evaluation['possible_dq'])
            ->take(3);

        if ($violations->isEmpty()) {
            return null;
        }

        return 'Possible DQ review required. '.$violations
            ->map(fn (array $evaluation): string => $evaluation['student']->full_name.': '.implode(' ', $evaluation['issues']))
            ->implode(' ');
    }

    /**
     * @param  Collection<int, Student>  $students
     *
     * @throws ValidationException
     */
    public function assertStudentsMatchSportCategory(Collection $students, Sport $sport): void
    {
        if (! in_array($sport->participant_category, ['male', 'female'], true)) {
            return;
        }

        $ineligibleStudents = $students
            ->filter(fn (Student $student): bool => ! $sport->acceptsStudentGender($student->gender))
            ->take(3);

        if ($ineligibleStudents->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'student_ids' => $ineligibleStudents
                ->map(fn (Student $student): string => $student->full_name)
                ->implode(', ').' cannot join this '.$sport->participant_category_label.' category.',
        ]);
    }

    /** @return array<string, mixed> */
    public function evaluateStudent(Student $student, IntramuralEdition|int $edition, ?EditionSport $additionalSport = null): array
    {
        $editionId = $edition instanceof IntramuralEdition ? $edition->id : $edition;
        $entries = AthleteEntry::query()
            ->with(['editionSport.sport'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $editionId))
            ->get();

        $evaluation = $this->evaluateEntries($entries, $additionalSport, $student);
        $evaluation['student'] = $student;

        return $evaluation;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function flaggedStudents(IntramuralEdition $edition, ?Team $team = null): Collection
    {
        return Student::query()
            ->with('course')
            ->when($team, fn ($query) => $query->whereHas('teamMembers', fn ($memberQuery) => $memberQuery
                ->where('edition_id', $edition->id)
                ->where('team_id', $team->id)))
            ->whereHas('athleteEntries', fn ($query) => $query
                ->where('status', 'active')
                ->whereHas('editionSport', fn ($editionSportQuery) => $editionSportQuery->where('edition_id', $edition->id)))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Student $student): array => $this->evaluateStudent($student, $edition))
            ->filter(fn (array $evaluation): bool => $evaluation['possible_dq'])
            ->values();
    }

    /**
     * Remove only the legacy open registration when the same student already
     * has the matching Men or Women registration for that discipline.
     */
    public function removeDuplicateOpenRegistrations(IntramuralEdition $edition): int
    {
        return DB::transaction(function () use ($edition): int {
            $entriesToRemove = $this->includeDualPartners($this->duplicateOpenEntries($edition))->unique('id')->values();
            if ($entriesToRemove->isEmpty()) {
                return 0;
            }

            $editionSportIds = $entriesToRemove->pluck('edition_sport_id')->unique()->values();
            abort_if(
                BracketMatch::query()
                    ->whereIn('edition_sport_id', $editionSportIds)
                    ->where('status', 'completed')
                    ->exists(),
                422,
                'Invalid registrations cannot be removed after a bracket result has been recorded. Remove them from the affected sport manually.'
            );

            $entryIds = $entriesToRemove->pluck('id');
            ScheduleParticipant::query()->whereIn('athlete_entry_id', $entryIds)->delete();
            BracketMatch::query()->whereIn('edition_sport_id', $editionSportIds)->delete();
            BracketCompetitor::query()->whereIn('edition_sport_id', $editionSportIds)->delete();

            foreach ($entriesToRemove as $entry) {
                $entry->delete();
            }

            return $entriesToRemove->count();
        });
    }

    public function duplicateOpenRegistrationCount(IntramuralEdition $edition): int
    {
        return $this->duplicateOpenEntries($edition)->count();
    }

    /**
     * @param  Collection<int, AthleteEntry>  $entries
     * @return array<string, mixed>
     */
    private function evaluateEntries(Collection $entries, ?EditionSport $additionalSport = null, ?Student $student = null): array
    {
        $items = $entries
            ->filter(fn (AthleteEntry $entry): bool => $entry->editionSport instanceof EditionSport && $this->isParticipationSport($entry->editionSport))
            ->map(fn (AthleteEntry $entry): array => $this->participationItem($entry->editionSport, $student))
            ->values();

        if ($additionalSport instanceof EditionSport && $this->isParticipationSport($additionalSport) && ! $items->contains(fn (array $item): bool => $item['edition_sport_id'] === (int) $additionalSport->id)) {
            $items->push($this->participationItem($additionalSport, $student));
        }

        $hasQualifyingRun = $items->contains('is_qualifying_run', true);
        $items = $items->map(function (array $item) use ($hasQualifyingRun): array {
            if ($item['is_relay']) {
                $item['counts_toward_limit'] = ! $hasQualifyingRun;
                $item['slot_label'] = $hasQualifyingRun ? 'Relay' : 'Relay (Individual/Dual)';
            }

            return $item;
        });
        $countedItems = $items->where('counts_toward_limit', true)->unique('participation_key');

        $counts = [
            self::SLOT_MAJOR => 0,
            self::SLOT_MINOR => 0,
            self::SLOT_INDIVIDUAL_DUAL => 0,
            self::SLOT_OTHER => 0,
        ];

        foreach ($countedItems as $item) {
            $counts[$item['slot']] = ($counts[$item['slot']] ?? 0) + 1;
        }

        $issues = [];
        $total = $countedItems->count();
        $hasMajorOrMinor = $counts[self::SLOT_MAJOR] > 0 || $counts[self::SLOT_MINOR] > 0;
        $maxEvents = $hasMajorOrMinor ? $this->maxEvents() : $this->individualDualOnlyMaxEvents();

        if ($total > $maxEvents) {
            $issues[] = 'More than '.$maxEvents.' events registered.';
        }

        if ($counts[self::SLOT_MAJOR] > 1) {
            $issues[] = 'More than 1 major event.';
        }

        if ($counts[self::SLOT_MINOR] > 1) {
            $issues[] = 'More than 1 minor event.';
        }

        if ($counts[self::SLOT_INDIVIDUAL_DUAL] > $maxEvents) {
            $issues[] = 'Too many individual/dual events.';
        }

        if ($total === $maxEvents && ! $this->matchesAllowedCombination($counts)) {
            $issues[] = 'Event combination does not match the allowed pairings.';
        }

        return [
            'possible_dq' => $issues !== [],
            'issues' => $issues,
            'counts' => $counts,
            'total' => $total,
            'entries' => $items,
            'summary' => $this->summaryFromCounts($counts),
        ];
    }

    /** @return Collection<int, AthleteEntry> */
    private function duplicateOpenEntries(IntramuralEdition $edition): Collection
    {
        return AthleteEntry::query()
            ->with(['student', 'editionSport.sport'])
            ->where('status', 'active')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->get()
            ->groupBy('student_id')
            ->flatMap(function (Collection $entries): Collection {
                $genderedDisciplines = $entries
                    ->filter(fn (AthleteEntry $entry): bool => $entry->student instanceof Student && $this->isGenderMatchedEntry($entry->student, $entry))
                    ->map(fn (AthleteEntry $entry): string => $this->disciplineKey($entry))
                    ->unique();

                return $entries->filter(fn (AthleteEntry $entry): bool => $this->isOpenEntry($entry)
                    && $genderedDisciplines->contains($this->disciplineKey($entry)));
            })
            ->values();
    }

    /** @return Collection<int, AthleteEntry> */
    private function includeDualPartners(Collection $entries): Collection
    {
        $dualPairs = $entries
            ->filter(fn (AthleteEntry $entry): bool => $entry->editionSport?->participant_type === 'dual' && filled($entry->pair_key))
            ->groupBy('edition_sport_id')
            ->map(fn (Collection $group): Collection => $group->pluck('pair_key')->unique());

        foreach ($dualPairs as $editionSportId => $pairKeys) {
            $entries = $entries->merge(
                AthleteEntry::query()
                    ->with('editionSport.sport')
                    ->where('edition_sport_id', $editionSportId)
                    ->where('status', 'active')
                    ->whereIn('pair_key', $pairKeys)
                    ->get()
            );
        }

        return $entries;
    }

    private function disciplineKey(AthleteEntry $entry): string
    {
        $code = Str::upper((string) $entry->editionSport?->sport?->code);

        return preg_replace('/-(MEN|WOMEN)$/', '', $code) ?: $code;
    }

    private function isGenderMatchedEntry(Student $student, AthleteEntry $entry): bool
    {
        return in_array($entry->editionSport?->sport?->participant_category, ['male', 'female'], true)
            && strcasecmp((string) $student->gender, (string) $entry->editionSport->sport->participant_category) === 0;
    }

    private function isOpenEntry(AthleteEntry $entry): bool
    {
        return ! in_array($entry->editionSport?->sport?->participant_category, ['male', 'female'], true);
    }

    private function matchesAllowedCombination(array $counts): bool
    {
        return collect($this->rulesForDisplay())->contains(function (array $combination) use ($counts): bool {
            return (int) ($combination[self::SLOT_MAJOR] ?? 0) === $counts[self::SLOT_MAJOR]
                && (int) ($combination[self::SLOT_MINOR] ?? 0) === $counts[self::SLOT_MINOR]
                && (int) ($combination[self::SLOT_INDIVIDUAL_DUAL] ?? 0) === $counts[self::SLOT_INDIVIDUAL_DUAL]
                && $counts[self::SLOT_OTHER] === 0;
        });
    }

    private function summaryFromCounts(array $counts): string
    {
        return 'Major '.$counts[self::SLOT_MAJOR]
            .' / Minor '.$counts[self::SLOT_MINOR]
            .' / Individual-Dual '.$counts[self::SLOT_INDIVIDUAL_DUAL]
            .' / Other '.$counts[self::SLOT_OTHER];
    }

    private function countsForParticipation(EditionSport $editionSport): bool
    {
        $editionSport->loadMissing('sport');
        $sportCode = Str::upper((string) $editionSport->sport?->code);

        if (($editionSport->scoring_rules['non_scoring'] ?? false) === true) {
            return false;
        }

        return ! Str::startsWith($sportCode, 'CULT-');
    }

    /** @return array<string, int|string|bool> */
    private function participationItem(EditionSport $editionSport, ?Student $student = null): array
    {
        $editionSport->loadMissing('sport');
        $slot = $this->slotFor($editionSport);
        $isRelay = $this->isRelayEvent($editionSport);
        $isPairedPartner = $this->isPairedPartnerExempt($editionSport, $student);

        return [
            'edition_sport_id' => (int) $editionSport->id,
            'participation_key' => $isRelay ? $editionSport->sport->relayParticipationKey() : 'event:'.$editionSport->id,
            'name' => $editionSport->sport?->name ?? 'Unknown event',
            'slot' => $slot,
            'slot_label' => $isRelay ? 'Relay (Individual/Dual)' : ($isPairedPartner ? 'Paired partner (exempt)' : $this->slotLabel($slot)),
            'counts_toward_limit' => ! $isPairedPartner,
            'is_relay' => $isRelay,
            'is_paired_partner' => $isPairedPartner,
            'is_qualifying_run' => $this->sportCodeIsIn($editionSport, config('intramurals.participation_rules.relay_qualifying_run_sport_codes', [])),
        ];
    }

    /** @param array<int, string> $sportCodes */
    private function sportCodeIsIn(EditionSport $editionSport, array $sportCodes): bool
    {
        $editionSport->loadMissing('sport');
        $sportCode = Str::upper((string) $editionSport->sport?->code);

        return $sportCode !== '' && collect($sportCodes)
            ->map(fn (string $code): string => Str::upper($code))
            ->contains($sportCode);
    }

    private function pointSystemKey(EditionSport $editionSport): ?string
    {
        $configured = $editionSport->scoring_rules['point_system'] ?? null;
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $editionSport->loadMissing('sport');
        $sportCode = Str::upper((string) $editionSport->sport?->code);

        if ($sportCode === '') {
            return null;
        }

        foreach (config('intramurals.point_systems', []) as $key => $system) {
            $codes = collect($system['codes'] ?? [])->map(fn (string $code): string => Str::upper($code));
            if ($codes->contains($sportCode)) {
                return $key;
            }
        }

        return null;
    }
}
