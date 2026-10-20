<?php

namespace App\Services;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StandingsService
{
    public const DEFAULT_PLACEMENTS = [
        ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
        ['placement' => 2, 'label' => 'Runner-up', 'points' => 7.0, 'medal' => 'silver'],
        ['placement' => 3, 'label' => 'Third place', 'points' => 5.0, 'medal' => 'bronze'],
    ];

    /** @return array{placements: array<int, array{placement: int, label: string, points: float, medal: string|null}>} */
    public static function defaultScoringRules(): array
    {
        return ['placements' => self::DEFAULT_PLACEMENTS];
    }

    public function activeEdition(): ?IntramuralEdition
    {
        return IntramuralEdition::query()
            ->where('status', 'active')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();
    }

    /** @return Collection<int, EditionSport> */
    public function scoringEventsFor(?IntramuralEdition $edition): Collection
    {
        if ($edition === null) {
            return collect();
        }

        $events = $edition->editionSports()
            ->whereHas('sport', fn ($query) => $query->where('status', 'active'))
            ->with(['sport', 'sportResult.submitter'])
            ->get();
        $currentSportIds = Sport::withoutSupersededOpenSports($events->pluck('sport'))->pluck('id');

        return $events->filter(fn (EditionSport $event): bool => $event->status !== 'archived' && $currentSportIds->contains($event->sport_id) && $this->pointRulesFor($event) !== [])
            ->sortBy(fn (EditionSport $event): string => $event->sport->name)
            ->values();
    }

    /** @return Collection<int, array{team: Team, tally: TeamTally, rank: int}> */
    public function standingsFor(?IntramuralEdition $edition): Collection
    {
        if (! $edition instanceof IntramuralEdition) {
            return collect();
        }

        $tallies = TeamTally::query()
            ->where('edition_id', $edition->id)
            ->get()
            ->keyBy('team_id');

        return Team::query()
            ->with('course')
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team): array => [
                'team' => $team,
                'tally' => $tallies->get($team->id) ?? new TeamTally([
                    'edition_id' => $edition->id,
                    'team_id' => $team->id,
                    'gold_count' => 0,
                    'silver_count' => 0,
                    'bronze_count' => 0,
                    'points' => 0,
                    'updated_at' => null,
                ]),
                'rank' => 0,
            ])
            ->sort(function (array $a, array $b): int {
                foreach (['points', 'gold_count', 'silver_count', 'bronze_count'] as $field) {
                    $left = (float) $a['tally']->{$field};
                    $right = (float) $b['tally']->{$field};

                    if ($left !== $right) {
                        return $right <=> $left;
                    }
                }

                return strcasecmp($a['team']->name, $b['team']->name);
            })
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    public function declaredResultsFor(?IntramuralEdition $edition): Collection
    {
        if ($edition === null) {
            return collect();
        }

        $teams = Team::withTrashed()->where('edition_id', $edition->id)->get()->keyBy('id');

        return $this->scoringEventsFor($edition)
            ->filter(fn (EditionSport $event): bool => $event->sportResult !== null)
            ->sortByDesc(fn (EditionSport $event): int => $event->sportResult->submitted_at?->timestamp ?? 0)
            ->map(function (EditionSport $event) use ($teams): array {
                $rules = collect($this->pointRulesFor($event))->keyBy('placement');
                $placements = collect($event->sportResult->placements_json ?? [])
                    ->map(function ($teamId, $place) use ($rules, $teams): ?array {
                        $rule = $rules->get((int) $place);
                        if ($rule === null) {
                            return null;
                        }
                        $team = $teams->get((int) $teamId);

                        return [
                            ...$rule,
                            'team_name' => $team?->name ?? 'Faction unavailable',
                            'team_logo' => $team?->logo_path,
                        ];
                    })->filter()->sortBy('placement')->values();
                $category = str_starts_with(strtoupper($event->sport->code), 'CULT-')
                    ? 'cultural' : ($event->sport->isTrackAndField() ? 'track-field' : 'sports');

                return [
                    'id' => $event->id,
                    'name' => preg_replace('/^Cultural(?: Bonus)?:\s*/i', '', $event->sport->name),
                    'category' => $category,
                    'category_label' => ['sports' => 'Sports', 'cultural' => 'Cultural', 'track-field' => 'Track & Field'][$category],
                    'declared_at' => $event->sportResult->submitted_at,
                    'placements' => $placements,
                ];
            })->values();
    }

    /** @return array{teams: array<int, array<string, mixed>>, preview: bool, updatedAt: mixed, results: Collection} */
    public function landingData(): array
    {
        $configuredTeams = collect(config('landing.teams', []));
        $edition = $this->activeEdition();
        $standings = $this->standingsFor($edition);
        $standingByName = $standings->keyBy(fn (array $row): string => Str::lower($row['team']->name));
        $updatedAt = $standings
            ->map(fn (array $row) => $row['tally']->updated_at)
            ->filter()
            ->max();

        return [
            'teams' => $configuredTeams
                ->map(function (array $configuredTeam) use ($standingByName, $updatedAt): array {
                    $standing = $standingByName->get(Str::lower($configuredTeam['name'] ?? ''));

                    if ($standing !== null && $updatedAt !== null) {
                        $configuredTeam['score'] = (float) $standing['tally']->points;
                        $configuredTeam['rank'] = $standing['rank'];
                    }

                    return $configuredTeam;
                })
                ->all(),
            'preview' => $updatedAt === null,
            'updatedAt' => $updatedAt,
            'results' => $this->declaredResultsFor($edition),
        ];
    }

    /** @return array<int, array{placement: int, label: string, points: float, medal: string|null}> */
    public function pointRulesFor(EditionSport $editionSport): array
    {
        $rules = $editionSport->scoring_rules;
        if (is_array($rules) && ($rules['non_scoring'] ?? false) === true) {
            return [];
        }

        $placements = is_array($rules) && isset($rules['placements']) && is_array($rules['placements'])
            ? $rules['placements']
            : self::DEFAULT_PLACEMENTS;

        return collect($placements)
            ->map(function (array $rule, int|string $key): array {
                $placement = (int) ($rule['placement'] ?? $key);

                return [
                    'placement' => $placement,
                    'label' => (string) ($rule['label'] ?? match ($placement) {
                        1 => 'Champion',
                        2 => 'Runner-up',
                        3 => 'Third place',
                        default => 'Place '.$placement,
                    }),
                    'points' => (float) ($rule['points'] ?? 0),
                    'medal' => $this->normalizeMedal($rule['medal'] ?? null),
                ];
            })
            ->filter(fn (array $rule): bool => $rule['placement'] > 0)
            ->sortBy('placement')
            ->values()
            ->all();
    }

    /** More than one placed athlete can represent a faction in track and field. */
    public function allowsRepeatedWinnersFor(EditionSport $editionSport): bool
    {
        // Several placed athletes can represent the same faction, even with only three places.
        return $editionSport->sport?->isTrackAndField() || count($this->pointRulesFor($editionSport)) > 3;
    }

    /** @param array<int|string, int|string|null> $placements */
    public function recordSportResult(EditionSport $editionSport, array $placements, User $submittedBy): SportResult
    {
        $editionSport->loadMissing('edition');
        abort_unless($editionSport->edition?->status === 'active', 404);

        return DB::transaction(function () use ($editionSport, $placements, $submittedBy): SportResult {
            $normalizedPlacements = collect($placements)
                ->filter(fn ($teamId): bool => filled($teamId))
                ->mapWithKeys(fn ($teamId, int|string $placement): array => [(string) (int) $placement => (int) $teamId])
                ->all();

            $points = collect($this->pointRulesFor($editionSport))
                ->mapWithKeys(fn (array $rule): array => [(string) $rule['placement'] => $rule])
                ->all();

            $result = SportResult::query()->updateOrCreate(
                ['edition_sport_id' => $editionSport->id],
                [
                    'submitted_by' => $submittedBy->id,
                    'placements_json' => $normalizedPlacements,
                    'points_json' => $points,
                    'submitted_at' => now('UTC'),
                ],
            );

            $this->recalculateTallies($editionSport->edition);

            return $result;
        });
    }

    public function recalculateTallies(IntramuralEdition $edition): void
    {
        DB::transaction(function () use ($edition): void {
            $teams = Team::query()
                ->where('edition_id', $edition->id)
                ->where('status', 'active')
                ->get(['id']);

            $totals = $teams->mapWithKeys(fn (Team $team): array => [
                $team->id => [
                    'points' => 0.0,
                    'gold_count' => 0,
                    'silver_count' => 0,
                    'bronze_count' => 0,
                ],
            ])->all();

            $results = SportResult::query()
                ->with('editionSport')
                ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
                ->get();

            foreach ($results as $result) {
                $editionSport = $result->editionSport;
                if (! $editionSport instanceof EditionSport) {
                    continue;
                }

                $rules = collect($this->pointRulesFor($editionSport))
                    ->keyBy(fn (array $rule): string => (string) $rule['placement']);

                // Changing points must update saved result details as well as totals,
                // while keeping the original winners, submitter, and declaration time.
                if ($result->points_json != $rules->all()) {
                    $oldPoints = $result->points_json;
                    $result->timestamps = false;
                    $result->points_json = $rules->all();
                    $result->saveQuietly();
                    app(AuditService::class)->record('sport_result.points_recalculated', $result,
                        ['points_json' => $oldPoints], ['points_json' => $result->points_json]);
                }

                foreach (($result->placements_json ?? []) as $placement => $teamId) {
                    $teamId = (int) $teamId;
                    if (! array_key_exists($teamId, $totals)) {
                        continue;
                    }

                    $rule = $rules->get((string) (int) $placement);
                    $totals[$teamId]['points'] += (float) ($rule['points'] ?? 0);

                    match ($this->normalizeMedal($rule['medal'] ?? null)) {
                        'gold' => $totals[$teamId]['gold_count']++,
                        'silver' => $totals[$teamId]['silver_count']++,
                        'bronze' => $totals[$teamId]['bronze_count']++,
                        default => null,
                    };
                }
            }

            foreach ($totals as $teamId => $total) {
                TeamTally::query()->updateOrCreate(
                    ['edition_id' => $edition->id, 'team_id' => $teamId],
                    [
                        'points' => $total['points'],
                        'gold_count' => $total['gold_count'],
                        'silver_count' => $total['silver_count'],
                        'bronze_count' => $total['bronze_count'],
                        'updated_at' => $results->isEmpty() ? null : now(),
                    ],
                );
            }
        });
    }

    private function normalizeMedal(mixed $medal): ?string
    {
        if (! is_string($medal)) {
            return null;
        }

        return in_array($medal, ['gold', 'silver', 'bronze'], true) ? $medal : null;
    }
}
