<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use TournamentGenerator\Constants;
use TournamentGenerator\Group;
use TournamentGenerator\Helpers\Generator;

class BracketService
{
    private const SETTLED_STATUSES = ['completed', 'bye', 'void'];

    public function __construct(private readonly AuditService $auditService) {}

    public function initialize(EditionSport $editionSport): void
    {
        if (! $this->usesBracketEngine($editionSport) || BracketMatch::query()->where('edition_sport_id', $editionSport->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($editionSport): void {
            $this->generate($editionSport);
            if ($this->supportsManualLineupMarker()) {
                $editionSport->update(['bracket_lineup_configured_at' => null]);
            }
        });
    }

    public function reset(EditionSport $editionSport): void
    {
        abort_unless($this->usesBracketEngine($editionSport), 422, 'Only generated tournament brackets can be reset.');

        DB::transaction(function () use ($editionSport): void {
            $editionSport = EditionSport::query()->lockForUpdate()->findOrFail($editionSport->id);
            $previousMatches = BracketMatch::query()
                ->where('edition_sport_id', $editionSport->id)
                ->lockForUpdate()
                ->get();
            $before = [
                'match_count' => $previousMatches->count(),
                'completed_match_count' => $previousMatches->where('status', 'completed')->count(),
                'competitor_count' => BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->count(),
            ];

            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->delete();
            BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->delete();

            $this->generate($editionSport);

            $this->auditService->record('bracket.reset', $editionSport, $before, [
                'match_count' => BracketMatch::query()->where('edition_sport_id', $editionSport->id)->count(),
                'completed_match_count' => 0,
                'competitor_count' => BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->count(),
            ]);
        });
    }

    /**
     * Rebuild a bracket from the explicitly selected and ordered competitors.
     * This is deliberately separate from reset: registrations stay intact and
     * the competitor records remain available for future lineup changes.
     */
    public function reorder(EditionSport $editionSport, array $competitorIds): void
    {
        abort_unless($this->usesBracketEngine($editionSport), 422, 'This sport does not use a generated bracket.');

        DB::transaction(function () use ($editionSport, $competitorIds): void {
            $editionSport = EditionSport::query()->lockForUpdate()->findOrFail($editionSport->id);
            $matches = BracketMatch::query()
                ->where('edition_sport_id', $editionSport->id)
                ->lockForUpdate()
                ->get();
            $initialAssignment = $this->supportsManualLineupMarker() && $editionSport->bracket_lineup_configured_at === null;
            abort_if(! $initialAssignment && $matches->contains(fn (BracketMatch $match): bool => $match->status !== 'pending'), 422, 'The lineup cannot be changed after a bracket result has been recorded.');

            $competitors = BracketCompetitor::query()
                ->where('edition_sport_id', $editionSport->id)
                ->get()
                ->keyBy('id');
            $ids = collect($competitorIds)->map(fn ($id): int => (int) $id);
            abort_if($ids->count() < 2 || $ids->duplicates()->isNotEmpty(), 422, 'Choose at least two different competitors.');
            abort_if($ids->contains(fn (int $id): bool => ! $competitors->has($id)), 422, 'One or more selected competitors are not part of this sport.');

            $ordered = $ids->map(fn (int $id) => $competitors->get($id))->values();
            $before = $matches->flatMap(fn (BracketMatch $match) => [$match->competitor_one_id, $match->competitor_two_id])->filter()->unique()->values()->all();
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->delete();
            $this->generate($editionSport, $ordered);
            if ($this->supportsManualLineupMarker()) {
                $editionSport->update(['bracket_lineup_configured_at' => now()]);
            }

            $this->auditService->record('bracket.reordered', $editionSport, ['competitor_ids' => $before], [
                'competitor_ids' => $ordered->pluck('id')->all(),
                'match_count' => BracketMatch::query()->where('edition_sport_id', $editionSport->id)->count(),
            ]);
        });
    }

    public function record(BracketMatch $match, BracketCompetitor $winner): void
    {
        DB::transaction(function () use ($match, $winner): void {
            $match = BracketMatch::query()
                ->lockForUpdate()
                ->with(['editionSport', 'competitorOne', 'competitorTwo'])
                ->findOrFail($match->id);

            if ($match->status === 'completed') {
                throw ValidationException::withMessages(['winner_competitor_id' => 'This match already has a winner. Refresh the bracket to see the recorded result.']);
            }
            if ($match->status !== 'pending' || ! $match->competitor_one_id || ! $match->competitor_two_id) {
                throw ValidationException::withMessages(['winner_competitor_id' => 'Save a complete bracket lineup before declaring a winner.']);
            }
            if ((int) $winner->edition_sport_id !== (int) $match->edition_sport_id || ! in_array($winner->id, [$match->competitor_one_id, $match->competitor_two_id], true)) {
                throw ValidationException::withMessages(['winner_competitor_id' => 'The matchup has changed. Save the bracket lineup, then choose a competitor in this match.']);
            }

            $loser = $winner->id === $match->competitor_one_id ? $match->competitorTwo : $match->competitorOne;
            $match->update([
                'winner_competitor_id' => $winner->id,
                'loser_competitor_id' => $loser->id,
                'winner_team_id' => $winner->type === 'team' ? $winner->team_id : null,
                'loser_team_id' => $loser->type === 'team' ? $loser->team_id : null,
                'status' => 'completed',
            ]);
            $this->auditService->record('bracket.match.completed', $match, null, $match->fresh()->only($match->getFillable()));

            if (in_array($match->editionSport->game_mechanic, ['round_robin', 'custom'], true)) {
                return;
            }

            $this->advanceOutcome($match->fresh(), $winner, $loser);
            $this->settleAutomaticMatches($match->editionSport);
        });
    }

    private function generate(EditionSport $editionSport, ?Collection $competitors = null): void
    {
        $competitors ??= $this->synchronizeCompetitors($editionSport);

        if ($competitors->count() < 2) {
            return;
        }

        if (in_array($editionSport->game_mechanic, ['round_robin', 'custom'], true)) {
            $this->createRoundRobinSchedule($editionSport, $competitors);

            return;
        }

        if ($editionSport->game_mechanic === 'double_elimination') {
            $this->createDoubleElimination($editionSport, $competitors);
        } else {
            $this->createWinnersBracket($editionSport, $competitors);
        }

        $this->settleAutomaticMatches($editionSport);
    }

    private function supportsManualLineupMarker(): bool
    {
        return Schema::hasColumn('edition_sports', 'bracket_lineup_configured_at');
    }

    private function synchronizeCompetitors(EditionSport $editionSport): Collection
    {
        BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->delete();

        $entries = AthleteEntry::query()
            ->where('edition_sport_id', $editionSport->id)
            ->where('status', 'active')
            ->with(['student', 'team'])
            ->orderBy('team_id')
            ->orderBy('student_id')
            ->get();

        return match ($editionSport->participant_type) {
            'team' => $entries
                ->filter(fn (AthleteEntry $entry) => $entry->team !== null)
                ->groupBy('team_id')
                ->map(fn (Collection $group) => $this->storeCompetitor(
                    $editionSport,
                    'team',
                    'team:'.$group->first()->team_id,
                    $group->first()->team->name,
                    $group,
                    $group->first()->team_id,
                ))
                ->sortBy('label')
                ->values(),
            'dual' => $entries
                ->filter(fn (AthleteEntry $entry) => filled($entry->pair_key))
                ->groupBy('pair_key')
                ->filter(fn (Collection $group) => $group->count() === 2)
                ->map(fn (Collection $group, string $pairKey) => $this->storeCompetitor(
                    $editionSport,
                    'dual',
                    'dual:'.$pairKey,
                    $group->sortBy('student.full_name')->map(fn (AthleteEntry $entry) => $entry->student->full_name)->implode(' / '),
                    $group,
                    $group->first()->team_id,
                ))
                ->sortBy('label')
                ->values(),
            default => $entries
                ->map(fn (AthleteEntry $entry) => $this->storeCompetitor(
                    $editionSport,
                    'individual',
                    'student:'.$entry->student_id,
                    $entry->student->full_name,
                    collect([$entry]),
                ))
                ->sortBy('label')
                ->values(),
        };
    }

    private function storeCompetitor(EditionSport $editionSport, string $type, string $identityKey, string $label, Collection $entries, ?int $teamId = null): BracketCompetitor
    {
        return BracketCompetitor::query()->create([
            'edition_sport_id' => $editionSport->id,
            'type' => $type,
            'identity_key' => $identityKey,
            'team_id' => $teamId,
            'label' => $label,
            'athlete_entry_ids' => $entries->pluck('id')->sort()->values()->all(),
        ]);
    }

    private function createDoubleElimination(EditionSport $editionSport, Collection $competitors): void
    {
        $size = $this->createWinnersBracket($editionSport, $competitors);
        $winnerRounds = (int) log($size, 2);

        if ($winnerRounds > 1) {
            for ($round = 1; $round <= (2 * $winnerRounds) - 2; $round++) {
                $matchCount = $round === 1
                    ? (int) ($size / 4)
                    : (int) ($size / (2 ** (int) (intdiv($round + 1, 2) + 1)));

                for ($matchNumber = 1; $matchNumber <= $matchCount; $matchNumber++) {
                    $this->createMatch($editionSport, 'losers', $round, $matchNumber);
                }
            }
        }

        $this->createMatch($editionSport, 'finals', 1, 1);
        $this->createMatch($editionSport, 'finals', 2, 1);
    }

    private function createRoundRobinSchedule(EditionSport $editionSport, Collection $competitors): void
    {
        $group = new Group($editionSport->sport?->name ?? 'Round robin');
        $teams = [];

        foreach ($competitors as $competitor) {
            $teams[] = $group->team($competitor->label, $competitor->id);
        }

        if (count($teams) % 2 !== 0) {
            $teams[] = Constants::DUMMY_TEAM;
        }

        // Match adjacent manual slots in the opening round. The engine's
        // genGames() shuffles teams, so use its circle helpers directly to
        // preserve the administrator's chosen matchups when saving.
        $evenSlots = $oddSlots = [];
        foreach ($teams as $index => $team) {
            if ($index % 2 === 0) {
                $evenSlots[] = $team;
            } else {
                $oddSlots[] = $team;
            }
        }
        $teams = array_merge($evenSlots, array_reverse($oddSlots));

        for ($round = 1; $round < count($teams); $round++) {
            foreach (Generator::circle_saveBracket($teams, $group) as $index => $game) {
                [$oneId, $twoId] = $game->getTeamsIds();
                $this->createMatch(
                    $editionSport,
                    'round_robin',
                    $round,
                    $index + 1,
                    $competitors->firstWhere('id', $oneId),
                    $competitors->firstWhere('id', $twoId),
                );
            }
            $teams = Generator::circle_rotateBracket($teams);
        }
    }

    private function createWinnersBracket(EditionSport $editionSport, Collection $competitors): int
    {
        $size = $this->bracketSize($competitors->count());
        $slots = array_pad($competitors->values()->all(), $size, null);

        for ($round = 1, $matchCount = (int) ($size / 2); $matchCount >= 1; $round++, $matchCount = (int) ($matchCount / 2)) {
            for ($matchNumber = 1; $matchNumber <= $matchCount; $matchNumber++) {
                $this->createMatch(
                    $editionSport,
                    'winners',
                    $round,
                    $matchNumber,
                    $round === 1 ? $slots[($matchNumber - 1) * 2] : null,
                    $round === 1 ? $slots[(($matchNumber - 1) * 2) + 1] : null,
                );
            }
        }

        return $size;
    }

    private function createMatch(EditionSport $editionSport, string $bracket, int $round, int $matchNumber, ?BracketCompetitor $one = null, ?BracketCompetitor $two = null): BracketMatch
    {
        return BracketMatch::query()->create([
            'edition_sport_id' => $editionSport->id,
            'bracket' => $bracket,
            'round_number' => $round,
            'match_number' => $matchNumber,
            'competitor_one_id' => $one?->id,
            'competitor_two_id' => $two?->id,
            'team_one_id' => $one?->type === 'team' ? $one->team_id : null,
            'team_two_id' => $two?->type === 'team' ? $two->team_id : null,
            'status' => 'pending',
        ]);
    }

    private function settleAutomaticMatches(EditionSport $editionSport): void
    {
        $changed = true;

        while ($changed) {
            $changed = false;
            $rounds = $this->winnerRoundCount($editionSport);
            $matches = BracketMatch::query()
                ->where('edition_sport_id', $editionSport->id)
                ->where('status', 'pending')
                ->with(['competitorOne', 'competitorTwo'])
                ->orderBy('bracket')
                ->orderBy('round_number')
                ->orderBy('match_number')
                ->get();

            foreach ($matches as $match) {
                if (! $this->matchInputsAreSettled($match, $rounds)) {
                    continue;
                }

                $competitors = collect([$match->competitorOne, $match->competitorTwo])->filter();

                if ($competitors->count() === 0) {
                    $match->update(['status' => 'void']);
                    $changed = true;

                    continue;
                }

                if ($competitors->count() === 1) {
                    /** @var BracketCompetitor $winner */
                    $winner = $competitors->first();
                    $match->update([
                        'winner_competitor_id' => $winner->id,
                        'winner_team_id' => $winner->type === 'team' ? $winner->team_id : null,
                        'status' => 'bye',
                    ]);
                    $this->auditService->record('bracket.match.bye', $match, null, $match->fresh()->only($match->getFillable()));
                    $this->advanceOutcome($match->fresh(), $winner);
                    $changed = true;
                }
            }
        }
    }

    private function advanceOutcome(BracketMatch $match, BracketCompetitor $winner, ?BracketCompetitor $loser = null): void
    {
        $editionSport = $match->editionSport ?? EditionSport::findOrFail($match->edition_sport_id);
        $winnerRounds = $this->winnerRoundCount($editionSport);

        if ($match->bracket === 'winners') {
            if ($match->round_number < $winnerRounds) {
                $this->placeCompetitor(
                    $this->matchAt($editionSport, 'winners', $match->round_number + 1, (int) ceil($match->match_number / 2)),
                    $match->match_number % 2 === 1 ? 'one' : 'two',
                    $winner,
                );
            } elseif ($editionSport->game_mechanic === 'double_elimination') {
                $this->placeCompetitor($this->matchAt($editionSport, 'finals', 1, 1), 'one', $winner);
            }

            if ($editionSport->game_mechanic === 'double_elimination' && $loser) {
                $this->advanceWinnerBracketLoser($editionSport, $match, $loser, $winnerRounds);
            }

            return;
        }

        if ($match->bracket === 'losers') {
            $lastLoserRound = (2 * $winnerRounds) - 2;

            if ($match->round_number === $lastLoserRound) {
                $this->placeCompetitor($this->matchAt($editionSport, 'finals', 1, 1), 'two', $winner);
            } elseif ($match->round_number % 2 === 1) {
                $this->placeCompetitor($this->matchAt($editionSport, 'losers', $match->round_number + 1, $match->match_number), 'one', $winner);
            } else {
                $this->placeCompetitor(
                    $this->matchAt($editionSport, 'losers', $match->round_number + 1, (int) ceil($match->match_number / 2)),
                    $match->match_number % 2 === 1 ? 'one' : 'two',
                    $winner,
                );
            }

            return;
        }

        if ($match->bracket === 'finals' && $match->round_number === 1 && $match->competitor_two_id === $winner->id) {
            $reset = $this->matchAt($editionSport, 'finals', 2, 1);
            $this->placeCompetitor($reset, 'one', $match->competitorOne);
            $this->placeCompetitor($reset, 'two', $match->competitorTwo);
        }
    }

    private function advanceWinnerBracketLoser(EditionSport $editionSport, BracketMatch $match, BracketCompetitor $loser, int $winnerRounds): void
    {
        if ($winnerRounds === 1) {
            $this->placeCompetitor($this->matchAt($editionSport, 'finals', 1, 1), 'two', $loser);

            return;
        }

        if ($match->round_number === 1) {
            $this->placeCompetitor(
                $this->matchAt($editionSport, 'losers', 1, (int) ceil($match->match_number / 2)),
                $match->match_number % 2 === 1 ? 'one' : 'two',
                $loser,
            );

            return;
        }

        $this->placeCompetitor($this->matchAt($editionSport, 'losers', (2 * $match->round_number) - 2, $match->match_number), 'two', $loser);
    }

    private function placeCompetitor(BracketMatch $match, string $slot, BracketCompetitor $competitor): void
    {
        $competitorColumn = 'competitor_'.$slot.'_id';
        $teamColumn = 'team_'.$slot.'_id';

        if ($match->{$competitorColumn} && $match->{$competitorColumn} !== $competitor->id) {
            abort(409, 'The bracket has conflicting competitor progression.');
        }

        if ($match->{$competitorColumn} === $competitor->id) {
            return;
        }

        $match->update([
            $competitorColumn => $competitor->id,
            $teamColumn => $competitor->type === 'team' ? $competitor->team_id : null,
        ]);
    }

    private function matchInputsAreSettled(BracketMatch $match, int $winnerRounds): bool
    {
        $editionSportId = $match->edition_sport_id;

        if ($match->bracket === 'winners') {
            return $match->round_number === 1 || $this->positionsAreSettled($editionSportId, [
                ['winners', $match->round_number - 1, ($match->match_number * 2) - 1],
                ['winners', $match->round_number - 1, $match->match_number * 2],
            ]);
        }

        if ($match->bracket === 'losers') {
            if ($match->round_number === 1) {
                return $this->positionsAreSettled($editionSportId, [
                    ['winners', 1, ($match->match_number * 2) - 1],
                    ['winners', 1, $match->match_number * 2],
                ]);
            }

            if ($match->round_number % 2 === 1) {
                return $this->positionsAreSettled($editionSportId, [
                    ['losers', $match->round_number - 1, ($match->match_number * 2) - 1],
                    ['losers', $match->round_number - 1, $match->match_number * 2],
                ]);
            }

            return $this->positionsAreSettled($editionSportId, [
                ['losers', $match->round_number - 1, $match->match_number],
                ['winners', (int) (($match->round_number + 2) / 2), $match->match_number],
            ]);
        }

        if ($match->bracket === 'finals' && $match->round_number === 1) {
            return $winnerRounds === 1
                ? $this->positionsAreSettled($editionSportId, [['winners', 1, 1]])
                : $this->positionsAreSettled($editionSportId, [
                    ['winners', $winnerRounds, 1],
                    ['losers', (2 * $winnerRounds) - 2, 1],
                ]);
        }

        return $match->bracket === 'finals' && $match->round_number === 2
            ? $this->positionsAreSettled($editionSportId, [['finals', 1, 1]])
            : false;
    }

    private function positionsAreSettled(int $editionSportId, array $positions): bool
    {
        $matches = collect($positions)->map(fn (array $position) => BracketMatch::query()
            ->where('edition_sport_id', $editionSportId)
            ->where('bracket', $position[0])
            ->where('round_number', $position[1])
            ->where('match_number', $position[2])
            ->first());

        return $matches->every(fn (?BracketMatch $source) => $source && in_array($source->status, self::SETTLED_STATUSES, true));
    }

    private function matchAt(EditionSport $editionSport, string $bracket, int $round, int $matchNumber): BracketMatch
    {
        return BracketMatch::query()
            ->where('edition_sport_id', $editionSport->id)
            ->where('bracket', $bracket)
            ->where('round_number', $round)
            ->where('match_number', $matchNumber)
            ->firstOrFail();
    }

    private function winnerRoundCount(EditionSport $editionSport): int
    {
        return (int) BracketMatch::query()
            ->where('edition_sport_id', $editionSport->id)
            ->where('bracket', 'winners')
            ->max('round_number');
    }

    private function bracketSize(int $competitorCount): int
    {
        return 2 ** (int) ceil(log(max($competitorCount, 2), 2));
    }

    public function usesBracketEngine(EditionSport $editionSport): bool
    {
        if ($editionSport->sport?->isTrackAndField()) {
            return false;
        }

        return in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin', 'custom'], true);
    }
}
