<?php

namespace App\Services;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Team;
use App\Models\TeamTally;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StandingsService
{
    public const SCORE_GROUPS = ['student', 'employee'];

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
    public function standingsFor(?IntramuralEdition $edition, string $scoreGroup = 'student'): Collection
    {
        if (! $edition instanceof IntramuralEdition) {
            return collect();
        }

        $scoreGroup = $this->normalizeScoreGroup($scoreGroup);
        $tallies = $scoreGroup === 'student'
            ? TeamTally::query()
                ->where('edition_id', $edition->id)
                ->get()
                ->keyBy('team_id')
            : $this->employeeTalliesFor($edition);

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

    /**
     * Public standings use only explicitly published score snapshots. Draft
     * declarations remain available to the operations workspaces and do not
     * change the totals visitors see on the landing page.
     *
     * @return Collection<int, array{team: Team, tally: TeamTally, rank: int}>
     */
    public function publishedStandingsFor(?IntramuralEdition $edition, string $scoreGroup = 'student'): Collection
    {
        if (! $edition instanceof IntramuralEdition) {
            return collect();
        }

        $scoreGroup = $this->normalizeScoreGroup($scoreGroup);
        $teams = Team::query()
            ->with('course')
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $totals = $teams->mapWithKeys(fn (Team $team): array => [$team->id => [
            'points' => 0.0,
            'gold_count' => 0,
            'silver_count' => 0,
            'bronze_count' => 0,
        ]])->all();

        foreach ($this->declaredResultsFor($edition, true)->filter(fn (array $result): bool => ($result['score_group'] ?? 'student') === $scoreGroup) as $result) {
            foreach ($result['placements'] as $placement) {
                $teamId = (int) ($placement['team_id'] ?? 0);
                if (! array_key_exists($teamId, $totals)) {
                    continue;
                }
                $totals[$teamId]['points'] += (float) ($placement['points'] ?? 0);
                match ($this->normalizeMedal($placement['medal'] ?? null)) {
                    'gold' => $totals[$teamId]['gold_count']++,
                    'silver' => $totals[$teamId]['silver_count']++,
                    'bronze' => $totals[$teamId]['bronze_count']++,
                    default => null,
                };
            }
        }

        return $teams
            ->map(fn (Team $team): array => [
                'team' => $team,
                'tally' => new TeamTally([
                    'edition_id' => $edition->id,
                    'team_id' => $team->id,
                    ...$totals[$team->id],
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
    public function declaredResultsFor(?IntramuralEdition $edition, bool $publishedOnly = true): Collection
    {
        if ($edition === null) {
            return collect();
        }

        $teams = Team::withTrashed()->where('edition_id', $edition->id)->get()->keyBy('id');

        return $this->scoringEventsFor($edition)
            ->filter(fn (EditionSport $event): bool => $event->sportResult !== null
                && (! $publishedOnly || $event->sportResult->published_placements_json !== null))
            ->sortByDesc(fn (EditionSport $event): int => ($publishedOnly
                ? $event->sportResult->score_published_at
                : $event->sportResult->submitted_at)?->timestamp ?? 0)
            ->map(function (EditionSport $event) use ($teams, $publishedOnly): array {
                $result = $event->sportResult;
                $rules = $publishedOnly && $result->published_points_json !== null
                    ? collect($result->published_points_json)->mapWithKeys(fn (array $rule, int|string $key): array => [(int) ($rule['placement'] ?? $key) => $rule])
                    : collect($this->pointRulesFor($event))->keyBy('placement');
                $rules = $rules->map(fn (array $rule, int|string $placement): array => [
                    ...$rule,
                    'placement' => (int) ($rule['placement'] ?? $placement),
                    'points' => (float) ($rule['points'] ?? 0),
                ]);
                $placementsJson = $publishedOnly
                    ? ($result->published_placements_json ?? [])
                    : ($result->placements_json ?? []);
                $placements = collect($placementsJson)
                    ->map(function ($teamId, $place) use ($rules, $teams): ?array {
                        $rule = $rules->get((int) $place);
                        if ($rule === null) {
                            return null;
                        }
                        $team = $teams->get((int) $teamId);

                        return [
                            ...$rule,
                            'team_id' => (int) $teamId,
                            'team_name' => $team?->name ?? 'Faction unavailable',
                            'team_logo' => $team?->logo_path,
                        ];
                    })->filter(fn (?array $placement): bool => $placement !== null && (float) ($placement['points'] ?? 0) > 0)
                    ->sortBy('placement')->values();
                $category = str_starts_with(strtoupper($event->sport->code), 'CULT-')
                    ? 'cultural' : ($event->sport->isTrackAndField() ? 'track-field' : 'sports');

                return [
                    'id' => $event->id,
                    'name' => preg_replace('/^Cultural(?: Bonus)?:\s*/i', '', $event->sport->name),
                    'category' => $category,
                    'category_label' => ['sports' => 'Sports', 'cultural' => 'Cultural', 'track-field' => 'Track & Field'][$category],
                    'score_group' => $this->normalizeScoreGroup($result->score_group ?? 'student'),
                    'declared_at' => $publishedOnly ? ($result->score_published_at ?? $result->submitted_at) : $result->submitted_at,
                    'placements' => $placements,
                ];
            })->values();
    }

    /** @return array{teams: array<int, array<string, mixed>>, preview: bool, updatedAt: mixed, results: Collection} */
    public function landingData(): array
    {
        $configuredTeams = collect(config('landing.teams', []));
        $edition = $this->activeEdition();
        $standings = $this->publishedStandingsFor($edition);
        $standingByName = $standings->keyBy(fn (array $row): string => Str::lower($row['team']->name));
        $updatedAt = $edition === null ? null : SportResult::query()
            ->whereNotNull('score_published_at')
            ->where('score_group', 'student')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->orderByDesc('score_published_at')
            ->value('score_published_at');
        $updatedAt = $updatedAt ? Carbon::parse($updatedAt, 'UTC') : null;

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
            'categoryStandings' => collect([
                'sports' => $this->categoryStandingsFor($edition, 'sports'),
                'cultural' => $this->categoryStandingsFor($edition, 'cultural'),
            ]),
        ];
    }

    /** @return Collection<int, array{team: Team, tally: TeamTally, rank: int}> */
    public function categoryStandingsFor(?IntramuralEdition $edition, string $category): Collection
    {
        if (! $edition instanceof IntramuralEdition) {
            return collect();
        }

        $category = $category === 'cultural' ? 'cultural' : 'sports';
        $teams = Team::query()
            ->with('course')
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $totals = $teams->mapWithKeys(fn (Team $team): array => [$team->id => [
            'points' => 0.0,
            'gold_count' => 0,
            'silver_count' => 0,
            'bronze_count' => 0,
        ]])->all();

        $results = $this->declaredResultsFor($edition)
            ->filter(function (array $result) use ($category): bool {
                if (($result['score_group'] ?? 'student') !== 'student') {
                    return false;
                }

                return $category === 'cultural'
                    ? $result['category'] === 'cultural'
                    : in_array($result['category'], ['sports', 'track-field'], true);
            });

        foreach ($results as $result) {
            foreach ($result['placements'] as $placement) {
                $teamId = (int) ($placement['team_id'] ?? 0);
                if (! array_key_exists($teamId, $totals)) {
                    continue;
                }
                $totals[$teamId]['points'] += (float) ($placement['points'] ?? 0);
                match ($this->normalizeMedal($placement['medal'] ?? null)) {
                    'gold' => $totals[$teamId]['gold_count']++,
                    'silver' => $totals[$teamId]['silver_count']++,
                    'bronze' => $totals[$teamId]['bronze_count']++,
                    default => null,
                };
            }
        }

        return $teams
            ->map(fn (Team $team): array => [
                'team' => $team,
                'tally' => new TeamTally([
                    'edition_id' => $edition->id,
                    'team_id' => $team->id,
                    ...$totals[$team->id],
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

    /**
     * Some event formats can award more than one placement to the same
     * faction. Cultural and athletics results have always worked this way;
     * Table Tennis also needs it because a faction may place in team, singles,
     * and doubles divisions (including more than one placement in a division).
     */
    public function allowsRepeatedWinnersFor(EditionSport $editionSport): bool
    {
        $editionSport->loadMissing('sport');
        $code = strtoupper((string) ($editionSport->sport?->code ?? ''));

        return str_starts_with($code, 'CULT-')
            || str_starts_with($code, 'TABLE-TENNIS')
            || $editionSport->sport?->isTrackAndField()
            || count($this->pointRulesFor($editionSport)) > 3;
    }

    /** @param array<int|string, int|string|null> $placements */
    public function recordSportResult(EditionSport $editionSport, array $placements, User $submittedBy, string $scoreGroup = 'student', bool $publishImmediately = true): SportResult
    {
        $scoreGroup = $this->normalizeScoreGroup($scoreGroup);
        $editionSport->loadMissing('edition');
        abort_unless($editionSport->edition?->status === 'active', 404);

        return DB::transaction(function () use ($editionSport, $placements, $submittedBy, $scoreGroup, $publishImmediately): SportResult {
            $editionSport = EditionSport::with(['edition', 'sport'])->lockForUpdate()->findOrFail($editionSport->id);
            if ($editionSport->status === 'archived' || ! $editionSport->sport || $editionSport->sport->status !== 'active'
                || $editionSport->edition?->status !== 'active') {
                throw ValidationException::withMessages(['edition_sport_id' => 'This event is no longer available for scoring. Refresh the event list.']);
            }
            $currentRules = $this->pointRulesFor($editionSport);
            if ($currentRules === [] || array_diff(array_map('intval', array_keys($placements)), array_column($currentRules, 'placement')) !== []) {
                throw ValidationException::withMessages(['placements' => 'The event point rules changed. Refresh the scoring form and choose the current placements.']);
            }

            $normalizedPlacements = collect($placements)
                ->filter(fn ($teamId): bool => filled($teamId))
                ->mapWithKeys(fn ($teamId, int|string $placement): array => [(string) (int) $placement => (int) $teamId])
                ->all();

            $points = collect($this->pointRulesFor($editionSport))
                ->mapWithKeys(fn (array $rule): array => [(string) $rule['placement'] => $rule])
                ->all();

            $result = SportResult::query()
                ->where('edition_sport_id', $editionSport->id)
                ->lockForUpdate()
                ->first();
            $attributes = [
                'submitted_by' => $submittedBy->id,
                'placements_json' => $normalizedPlacements,
                'points_json' => $points,
                'score_group' => $scoreGroup,
                'submitted_at' => now('UTC'),
            ];
            if ($publishImmediately) {
                $attributes += [
                    'published_placements_json' => $normalizedPlacements,
                    'published_points_json' => $points,
                    'score_published_at' => now('UTC'),
                    'score_published_by' => $submittedBy->id,
                ];
            } elseif ($result === null) {
                $attributes += [
                    'published_placements_json' => null,
                    'published_points_json' => null,
                    'score_published_at' => null,
                    'score_published_by' => null,
                ];
            }

            if ($result === null) {
                $result = SportResult::query()->create(['edition_sport_id' => $editionSport->id, ...$attributes]);
            } else {
                $result->fill($attributes);
                $result->save();
            }

            $this->recalculateTallies($editionSport->edition);

            return $result;
        });
    }

    public function hasPendingScoreUpdate(EditionSport $editionSport): bool
    {
        $result = $editionSport->sportResult;
        if (! $result instanceof SportResult) {
            return false;
        }

        $currentPoints = collect($this->pointRulesFor($editionSport))
            ->mapWithKeys(fn (array $rule): array => [(string) $rule['placement'] => $rule])
            ->all();

        return $result->published_placements_json === null
            || $result->published_placements_json !== $result->placements_json
            || $result->published_points_json !== $currentPoints;
    }

    public function publishPendingScores(IntramuralEdition $edition, User $publisher): int
    {
        return DB::transaction(function () use ($edition, $publisher): int {
            $events = $this->scoringEventsFor($edition);
            $published = 0;
            foreach ($events as $event) {
                $result = SportResult::query()
                    ->where('edition_sport_id', $event->id)
                    ->lockForUpdate()
                    ->first();
                if (! $result instanceof SportResult) {
                    continue;
                }

                $currentPoints = collect($this->pointRulesFor($event))
                    ->mapWithKeys(fn (array $rule): array => [(string) $rule['placement'] => $rule])
                    ->all();
                $pending = $result->published_placements_json === null
                    || $result->published_placements_json !== $result->placements_json
                    || $result->published_points_json !== $currentPoints;
                if (! $pending) {
                    continue;
                }

                $before = $result->only(['published_placements_json', 'published_points_json', 'score_published_at', 'score_published_by']);
                $result->published_placements_json = $result->placements_json;
                $result->published_points_json = $currentPoints;
                $result->score_published_at = now('UTC');
                $result->score_published_by = $publisher->id;
                $result->save();
                app(AuditService::class)->record('sport_result.score_published', $result, $before, $result->only(['published_placements_json', 'published_points_json', 'score_published_at', 'score_published_by']), $publisher);
                $published++;
            }

            return $published;
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
                if ($this->normalizeScoreGroup($result->score_group ?? 'student') !== 'student') {
                    continue;
                }
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

    public function normalizeScoreGroup(mixed $scoreGroup): string
    {
        return in_array($scoreGroup, self::SCORE_GROUPS, true) ? $scoreGroup : 'student';
    }

    /** @return Collection<int, TeamTally> */
    private function employeeTalliesFor(IntramuralEdition $edition): Collection
    {
        $teams = Team::query()
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->get(['id']);
        $totals = $teams->mapWithKeys(fn (Team $team): array => [$team->id => [
            'points' => 0.0,
            'gold_count' => 0,
            'silver_count' => 0,
            'bronze_count' => 0,
        ]])->all();

        $results = SportResult::query()
            ->with('editionSport')
            ->where('score_group', 'employee')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->get();

        foreach ($results as $result) {
            $event = $result->editionSport;
            if (! $event instanceof EditionSport) {
                continue;
            }
            $rules = collect($this->pointRulesFor($event))->keyBy(fn (array $rule): string => (string) $rule['placement']);
            foreach (($result->placements_json ?? []) as $placement => $teamId) {
                $teamId = (int) $teamId;
                if (! array_key_exists($teamId, $totals)) {
                    continue;
                }
                $rule = $rules->get((string) (int) $placement);
                if ($rule === null) {
                    continue;
                }
                $totals[$teamId]['points'] += (float) ($rule['points'] ?? 0);
                match ($this->normalizeMedal($rule['medal'] ?? null)) {
                    'gold' => $totals[$teamId]['gold_count']++,
                    'silver' => $totals[$teamId]['silver_count']++,
                    'bronze' => $totals[$teamId]['bronze_count']++,
                    default => null,
                };
            }
        }

        return collect($totals)->mapWithKeys(function (array $total, int|string $teamId) use ($edition): array {
            return [(int) $teamId => new TeamTally([
                'edition_id' => $edition->id,
                'team_id' => (int) $teamId,
                ...$total,
                'updated_at' => null,
            ])];
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
