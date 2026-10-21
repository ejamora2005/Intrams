<?php

namespace App\Services;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use Illuminate\Support\Collection;

class PointSystemService
{
    public function __construct(private readonly StandingsService $standings) {}

    public function keyFor(EditionSport $event): ?string
    {
        $explicit = $event->scoring_rules['point_system'] ?? null;
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $event->loadMissing('sport');
        foreach (config('intramurals.point_systems', []) as $key => $definition) {
            if (in_array($event->sport?->code, $definition['codes'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }

    /** @return Collection<int, EditionSport> */
    public function eventsFor(?IntramuralEdition $edition, string $key): Collection
    {
        return $this->events($edition)->filter(fn (EditionSport $event): bool => $this->keyFor($event) === $key)->values();
    }

    /** Current edited values, rather than replaying the bundled defaults. */
    public function rulesFor(IntramuralEdition $edition, string $key): array
    {
        $reference = $this->eventsFor($edition, $key)->first();

        return $reference ? $this->standings->pointRulesFor($reference) : $this->defaultRules($key);
    }

    /** @return Collection<string, array<string, mixed>> */
    public function options(?IntramuralEdition $edition, ?EditionSport $current = null): Collection
    {
        $events = $this->events($edition);

        return collect(config('intramurals.point_systems', []))->map(function (array $definition, string $key) use ($events, $current): array {
            $members = $events->filter(fn (EditionSport $event): bool => $this->keyFor($event) === $key);
            $reference = $current && $this->keyFor($current) === $key && ! ($current->scoring_rules['non_scoring'] ?? false) ? $current : $members->first();

            return [
                'key' => $key,
                'label' => $definition['label'],
                'category' => $definition['category'] ?? 'sports',
                'description' => $definition['description'] ?? '',
                'rules' => $reference ? $this->standings->pointRulesFor($reference) : $this->defaultRules($key),
            ];
        });
    }

    private function defaultRules(string $key): array
    {
        return $this->standings->pointRulesFor(new EditionSport(['scoring_rules' => [
            'placements' => config('intramurals.point_systems.'.$key.'.placements', []),
        ]]));
    }

    private function events(?IntramuralEdition $edition): Collection
    {
        if (! $edition) {
            return collect();
        }

        $events = $edition->editionSports()->with(['sport', 'sportResult'])
            ->whereIn('status', ['preparation', 'active'])
            ->whereHas('sport', fn ($query) => $query->where('status', 'active'))
            ->orderBy('id')->get();
        $currentIds = Sport::withoutSupersededOpenSports($events->pluck('sport'))->pluck('id');

        return $events->filter(fn (EditionSport $event): bool => $currentIds->contains($event->sport_id) && ! ($event->scoring_rules['non_scoring'] ?? false))
            ->sortBy(fn (EditionSport $event): string => $event->sport->name)->values();
    }
}
