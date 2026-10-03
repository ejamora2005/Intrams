<?php

namespace App\Services;

use App\Models\IntramuralEdition;
use App\Models\Sport;
use Illuminate\Support\Facades\DB;

class EditionService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): IntramuralEdition
    {
        return DB::transaction(function () use ($attributes): IntramuralEdition {
            $this->ensureNoOtherActiveEdition($attributes['status']);
            $edition = IntramuralEdition::query()->create($attributes);
            $this->syncDefaultSports($edition);
            $this->auditService->record('edition.created', $edition, null, $edition->only($edition->getFillable()));

            return $edition;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(IntramuralEdition $edition, array $attributes): IntramuralEdition
    {
        return DB::transaction(function () use ($edition, $attributes): IntramuralEdition {
            $this->ensureNoOtherActiveEdition($attributes['status'], $edition);
            $before = $edition->only($edition->getFillable());
            $edition->update($attributes);
            $this->syncDefaultSports($edition);
            $this->auditService->record('edition.updated', $edition, $before, $edition->only($edition->getFillable()));

            return $edition;
        });
    }

    public function archive(IntramuralEdition $edition): void
    {
        DB::transaction(function () use ($edition): void {
            $before = $edition->only($edition->getFillable());
            $edition->update(['status' => 'archived']);
            $this->auditService->record('edition.archived', $edition, $before, $edition->only($edition->getFillable()));
        });
    }

    /**
     * Ensure every system/default sport is configured exactly once for an edition.
     *
     * This is deliberately idempotent so it is safe for a newly created edition,
     * an edited edition, and existing editions refreshed by the default-sport seeder.
     */
    public function syncDefaultSports(IntramuralEdition $edition): void
    {
        foreach (Sport::query()->where('is_system', true)->get(['id', 'name', 'code']) as $sport) {
            $defaults = $this->defaultCompetitionConfiguration($sport);
            $configuration = $edition->editionSports()
                ->withTrashed()
                ->where('sport_id', $sport->id)
                ->first();

            if ($configuration !== null) {
                if ($configuration->trashed()) {
                    $configuration->restore();
                }

                if (empty($configuration->scoring_rules['placements'])) {
                    $configuration->update(['scoring_rules' => $defaults['scoring_rules']]);
                }

                continue;
            }

            $edition->editionSports()->create([
                'sport_id' => $sport->id,
                'participant_type' => $defaults['participant_type'],
                'game_mechanic' => $defaults['game_mechanic'],
                'scoring_rules' => $defaults['scoring_rules'],
                'rules' => $defaults['rules'],
                'status' => 'preparation',
            ]);
        }
    }

    /** @return array{participant_type: string, game_mechanic: string, scoring_rules: array<string, mixed>, rules: ?string} */
    private function defaultCompetitionConfiguration(Sport $sport): array
    {
        $catalogItem = collect(config('intramurals.default_competitions', []))
            ->firstWhere('code', $sport->code);

        return [
            'participant_type' => $catalogItem['participant_type'] ?? 'team',
            'game_mechanic' => $catalogItem['game_mechanic'] ?? 'single_elimination',
            'scoring_rules' => $catalogItem['scoring_rules'] ?? StandingsService::defaultScoringRules(),
            'rules' => $catalogItem['rules'] ?? null,
        ];
    }

    private function ensureNoOtherActiveEdition(string $status, ?IntramuralEdition $except = null): void
    {
        if ($status !== 'active') {
            return;
        }

        $query = IntramuralEdition::query()->where('status', 'active')->lockForUpdate();
        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        abort_if($query->exists(), 422, 'Close or archive the currently active intramurals edition before activating another one.');
    }
}
