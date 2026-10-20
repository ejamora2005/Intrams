<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use Illuminate\Support\Collection;

class ScheduleBracketService
{
    public function matches(?IntramuralEdition $edition): Collection
    {
        if (! $edition) {
            return collect();
        }

        $matches = BracketMatch::whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->with(['editionSport.sport', 'teamOne', 'teamTwo', 'competitorOne.team', 'competitorTwo.team', 'winner', 'winnerCompetitor', 'schedule'])
            ->orderBy('edition_sport_id')->orderBy('round_number')->orderBy('match_number')->get()->keyBy('id');
        $competitors = $matches->flatMap(fn ($match) => [$match->competitorOne, $match->competitorTwo])->filter()->unique('id');
        $entryIds = $competitors->flatMap(fn ($competitor) => $competitor->athlete_entry_ids ?? [])->unique();
        $entries = $entryIds->isNotEmpty() ? AthleteEntry::with('team')->whereIn('id', $entryIds)->get()->keyBy('id') : collect();
        foreach ($competitors as $competitor) {
            if ($competitor->team) {
                continue;
            }
            $teams = $entries->only($competitor->athlete_entry_ids ?? [])->pluck('team')->filter()->unique('id');
            if ($teams->count() === 1) {
                $competitor->setRelation('team', $teams->first());
            }
        }

        return $matches;
    }

    public function participant(BracketMatch $match, string $side): array
    {
        $competitor = $side === 'home' ? $match->competitorOne : $match->competitorTwo;
        $team = $competitor?->team ?? ($side === 'home' ? $match->teamOne : $match->teamTwo);

        return ['name' => $competitor?->label ?: ($team?->name ?? 'Awaiting competitor'), 'logo_path' => $team?->logo_path, 'short' => $team?->logo_path ? null : ($competitor ? 'Player' : 'TBA')];
    }

    public function options(Collection $matches): array
    {
        return $matches->mapWithKeys(function (BracketMatch $match): array {
            $home = $this->participant($match, 'home');
            $away = $this->participant($match, 'away');

            return [$match->id => [
                'label' => $match->editionSport->sport->name.' · Round '.$match->round_number.' / Game '.$match->match_number.' · '.$home['name'].' vs '.$away['name'],
                'edition_sport_id' => $match->edition_sport_id,
                'home' => $home, 'away' => $away,
                'starts_at' => $match->schedule?->starts_at->format('Y-m-d\TH:i') ?? '',
                'ends_at' => $match->schedule?->ends_at?->format('Y-m-d\TH:i') ?? '',
                'venue' => $match->schedule?->venue ?? '',
            ]];
        })->all();
    }

    /** Persist timing only when the administrator explicitly requests it. */
    public function saveGameTimes(IntramuralEdition $edition, array $games, ?string $defaultVenue): void
    {
        foreach ($games as $game) {
            if (empty($game['update_bracket_schedule'])) {
                continue;
            }
            foreach ($game['bracket_match_ids'] ?? [] as $id) {
                $match = BracketMatch::whereKey($id)->whereHas('editionSport', fn ($q) => $q->where('edition_id', $edition->id))->lockForUpdate()->firstOrFail();
                $schedule = CompetitionSchedule::where('bracket_match_id', $match->id)->lockForUpdate()->first();
                $before = $schedule?->only($schedule->getFillable());
                $schedule ??= new CompetitionSchedule(['bracket_match_id' => $match->id, 'edition_sport_id' => $match->edition_sport_id, 'status' => $match->status === 'completed' ? 'completed' : 'scheduled']);
                $schedule->fill(['starts_at' => $game['bracket_starts_at'], 'ends_at' => $game['bracket_ends_at'], 'venue' => ($game['bracket_venue'] ?? null) ?: ($defaultVenue ?: 'TBA')])->save();
                app(AuditService::class)->record('competition_schedule.bracket_link_updated', $schedule, $before, $schedule->only($schedule->getFillable()));
            }
        }
    }

    public function storedGames(array $games): array
    {
        return collect($games)->map(function ($game) {
            $stored = collect($game)->only(['home', 'away', 'bracket_match_ids', 'manual_teams'])->all();
            if (isset($stored['bracket_match_ids'])) {
                $stored['bracket_match_ids'] = array_map('intval', $stored['bracket_match_ids']);
                $stored['bracket_refs'] = BracketMatch::whereIn('id', $stored['bracket_match_ids'])->get()->map(fn ($match) => $match->only(['edition_sport_id', 'bracket', 'round_number', 'match_number']))->all();
            }
            if (isset($stored['manual_teams'])) {
                $stored['manual_teams'] = (bool) $stored['manual_teams'];
            }

            return $stored;
        })->values()->all();
    }

    /** A lineup rebuild replaces match IDs; the sport/round/game position stays stable. */
    public function linkedMatches(array $game, Collection $matches): Collection
    {
        if (empty($game['bracket_refs'])) {
            return $matches->only($game['bracket_match_ids'] ?? []);
        }

        return collect($game['bracket_refs'])->map(function (array $reference) use ($matches) {
            return $matches->first(fn ($match) => (int) $match->edition_sport_id === (int) $reference['edition_sport_id']
                && $match->bracket === $reference['bracket'] && $match->round_number === (int) $reference['round_number'] && $match->match_number === (int) $reference['match_number']);
        })->filter()->keyBy('id');
    }
}
