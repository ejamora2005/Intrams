<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Support\Collection;
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
        $pointSystem = $this->pointSystemKey($editionSport);

        return match ($pointSystem) {
            'sports_major' => self::SLOT_MAJOR,
            'sports_minor' => self::SLOT_MINOR,
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

    /**
     * @param  Collection<int, Student>  $students
     *
     * @throws ValidationException
     */
    public function assertStudentsCanJoin(Collection $students, IntramuralEdition $edition, EditionSport $additionalSport): void
    {
        $violations = $this->previewForStudents($students, $edition, $additionalSport)
            ->filter(fn (array $evaluation): bool => $evaluation['possible_dq'])
            ->take(3);

        if ($violations->isEmpty()) {
            return;
        }

        $message = $violations
            ->map(fn (array $evaluation): string => $evaluation['student']->full_name.': '.implode(' ', $evaluation['issues']))
            ->implode(' ');

        throw ValidationException::withMessages([
            'student_ids' => $message,
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

        $evaluation = $this->evaluateEntries($entries, $additionalSport);
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
     * @param  Collection<int, AthleteEntry>  $entries
     * @return array<string, mixed>
     */
    private function evaluateEntries(Collection $entries, ?EditionSport $additionalSport = null): array
    {
        $items = $entries
            ->filter(fn (AthleteEntry $entry): bool => $entry->editionSport instanceof EditionSport && $this->countsForParticipation($entry->editionSport))
            ->map(function (AthleteEntry $entry): array {
                $slot = $this->slotFor($entry->editionSport);

                return [
                    'name' => $entry->editionSport->sport?->name ?? 'Unknown event',
                    'slot' => $slot,
                    'slot_label' => $this->slotLabel($slot),
                ];
            })
            ->values();

        if ($additionalSport instanceof EditionSport && $this->countsForParticipation($additionalSport) && ! $items->contains(fn (array $item): bool => $item['name'] === ($additionalSport->sport?->name ?? ''))) {
            $additionalSport->loadMissing('sport');
            $slot = $this->slotFor($additionalSport);
            $items->push([
                'name' => $additionalSport->sport?->name ?? 'Selected event',
                'slot' => $slot,
                'slot_label' => $this->slotLabel($slot),
            ]);
        }

        $counts = [
            self::SLOT_MAJOR => 0,
            self::SLOT_MINOR => 0,
            self::SLOT_INDIVIDUAL_DUAL => 0,
            self::SLOT_OTHER => 0,
        ];

        foreach ($items as $item) {
            $counts[$item['slot']] = ($counts[$item['slot']] ?? 0) + 1;
        }

        $issues = [];
        $total = $items->count();
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
