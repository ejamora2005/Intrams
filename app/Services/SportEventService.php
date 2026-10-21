<?php

namespace App\Services;

use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SportEventService
{
    public function __construct(private readonly PointSystemService $pointSystems, private readonly EligibilityService $eligibility) {}

    public function initialScoring(IntramuralEdition $edition, string $system, string $participantType): array
    {
        return ['point_system' => $system, 'point_system_override' => true,
            'participation_slot' => match ($system) {
                'sports_major' => EligibilityService::SLOT_MAJOR,
                'sports_minor', 'sports_basketball_3x3' => EligibilityService::SLOT_MINOR,
                'sports_athletics' => EligibilityService::SLOT_INDIVIDUAL_DUAL,
                default => $participantType === 'team' ? EligibilityService::SLOT_MAJOR : EligibilityService::SLOT_INDIVIDUAL_DUAL,
            },
            'placements' => $this->pointSystems->rulesFor($edition, $system),
        ];
    }

    public function ensureEditable(IntramuralEdition $edition): void
    {
        if (! in_array($edition->status, ['draft', 'active'], true)) {
            throw ValidationException::withMessages(['edition_id' => 'This edition is archived. Its sports are read-only.']);
        }
    }

    public function ensureSport(Sport $sport): void
    {
        abort_if(str_starts_with(strtoupper($sport->code), 'CULT-'), 404);
    }

    public function version(EditionSport $event, Sport $sport): string
    {
        return hash('sha256', json_encode([
            $event->only(['id', 'edition_id', 'sport_id', 'participant_type', 'game_mechanic', 'rules', 'status', 'scoring_rules']),
            $sport->only(['name', 'description', 'participant_category', 'participant_category_label', 'status', 'deleted_at']),
        ], JSON_THROW_ON_ERROR));
    }

    public function checkVersion(?string $version, EditionSport $event, Sport $sport): void
    {
        if (! is_string($version) || ! hash_equals($this->version($event, $sport), $version)) {
            throw ValidationException::withMessages(['event_version' => 'This sport was changed by another user. Reload the page and review its current details before saving.']);
        }
    }

    public function mechanicFor(Sport $sport, string $mechanic): string
    {
        return $sport->isTrackAndField() || ($sport->participant_category === 'custom' && $mechanic === 'round_robin') ? 'custom' : $mechanic;
    }

    public function hasResults(EditionSport $event): bool
    {
        return $event->sportResult()->exists() || $event->schedules()->whereHas('results')->exists()
            || BracketMatch::where('edition_sport_id', $event->id)->where('status', 'completed')->exists();
    }

    public function assertCanArchive(EditionSport $event): void
    {
        if ($this->hasResults($event)) {
            throw ValidationException::withMessages(['event' => 'This sport has recorded results and cannot be archived. Its winners and points must be retained.']);
        }
    }

    /** Preserve the existing rules unless the administrator explicitly changes the linked system. */
    public function scoringFor(EditionSport $event, ?string $system, User $admin, ?string $password): array
    {
        $current = $this->pointSystems->keyFor($event);
        $scoring = $event->scoring_rules ?? [];
        if (! $system) {
            if ($current) {
                throw ValidationException::withMessages(['point_system' => 'Choose the point system for this sport.']);
            }

            return $scoring;
        }

        if ($current !== $system || empty($scoring['placements']) || ($scoring['non_scoring'] ?? false)) {
            $placements = $this->pointSystems->rulesFor($event->edition, $system);
            if ($event->sportResult) {
                if (! Hash::check((string) $password, $admin->password)) {
                    throw ValidationException::withMessages(['admin_password' => 'Enter your admin password to change points for a declared sport.']);
                }
                $places = array_column($placements, 'placement');
                foreach ($event->sportResult->placements_json ?? [] as $place => $winner) {
                    if (filled($winner) && ! in_array((int) $place, $places, true)) {
                        throw ValidationException::withMessages(['point_system' => 'This point system cannot represent every declared placement. Choose a system with all recorded places.']);
                    }
                }
            }
            $scoring = [...$scoring, 'placements' => $placements, 'non_scoring' => false];
        }

        $slot = $this->eligibility->slotFor($event);
        if ($slot === EligibilityService::SLOT_OTHER) {
            $slot = $event->participant_type === 'team' ? EligibilityService::SLOT_MAJOR : EligibilityService::SLOT_INDIVIDUAL_DUAL;
        }

        return [...$scoring, 'point_system' => $system, 'point_system_override' => true, 'participation_slot' => $slot];
    }

    public function assertFormatChangeSafe(EditionSport $event, array $data, string $mechanic): void
    {
        if ($event->game_mechanic !== $mechanic && BracketMatch::where('edition_sport_id', $event->id)->exists()) {
            throw ValidationException::withMessages(['game_mechanic' => 'This sport already has a bracket. Keep its competition format to preserve its lineup, schedules and results.']);
        }
        if ($event->participant_type !== $data['participant_type'] && ($event->athleteEntries()->exists() || $event->bracketCompetitors()->exists() || $this->hasResults($event))) {
            throw ValidationException::withMessages(['participant_type' => 'This sport already has participants or results. Keep its participation format to preserve them.']);
        }
    }
}
