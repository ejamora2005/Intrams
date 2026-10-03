<?php

namespace App\Services;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
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

    /** @return array{teams: array<int, array<string, mixed>>, preview: bool, updatedAt: mixed} */
    public function landingData(): array
    {
        $configuredTeams = collect(config('landing.teams', []));
        $standings = $this->standingsFor($this->activeEdition());
        $standingByName = $standings->keyBy(fn (array $row): string => Str::lower($row['team']->name));
        $updatedAt = $standings
            ->map(fn (array $row) => $row['tally']->updated_at)
            ->filter()
            ->max();

        return [
            'teams' => $configuredTeams
                ->map(function (array $configuredTeam) use ($standingByName): array {
                    $standing = $standingByName->get(Str::lower($configuredTeam['name'] ?? ''));

                    if ($standing !== null) {
                        $configuredTeam['score'] = (float) $standing['tally']->points;
                        $configuredTeam['rank'] = $standing['rank'];
                    }

                    return $configuredTeam;
                })
                ->all(),
            'preview' => $updatedAt === null,
            'updatedAt' => $updatedAt,
        ];
    }

    /** @return array<int, array{placement: int, label: string, points: float, medal: string|null}> */
    public function pointRulesFor(EditionSport $editionSport): array
    {
        $rules = $editionSport->scoring_rules;
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
     * @param  array<int|string, int|string|null>  $placements
     */
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
                    'submitted_at' => now(),
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
