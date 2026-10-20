<?php

namespace App\Services;

use App\Models\IntramuralEdition;
use App\Models\PublicProgramme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Public timetable, independent of saved match schedules and scoring. */
class PublicProgrammeService
{
    public function appliesTo(IntramuralEdition $edition): bool
    {
        if (PublicProgramme::where('edition_id', $edition->id)->exists()) {
            return true;
        }

        $names = collect(config('intramurals.default_edition.aliases', []))
            ->push(config('intramurals.default_edition.name'))->filter();

        return $edition->school_year === config('public_programme.school_year')
            && $names->contains($edition->name);
    }

    /** @return array<int, string> */
    public function datesFor(IntramuralEdition $edition): array
    {
        return $this->appliesTo($edition)
            ? $this->eventsFor($edition)->where('is_published', true)->pluck('date')->unique()->sort()->values()->all()
            : [];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function eventsFor(IntramuralEdition $edition): Collection
    {
        $saved = PublicProgramme::where('edition_id', $edition->id)->first();

        return ($saved ? collect($saved->events) : $this->initialEvents())
            ->sortBy(fn (array $event): string => $event['date'].'-'
                .['AM' => 0, 'PM' => 1, 'Evening' => 2][$event['session']].'-'
                .($event['starts_at'] ?? ['AM' => '00:00', 'PM' => '12:00', 'Evening' => '18:00'][$event['session']]).'-'
                .sprintf('%06d', $event['position']))->values();
    }

    /** @return array<string, mixed>|null */
    public function sourceFor(IntramuralEdition $edition): ?array
    {
        return PublicProgramme::where('edition_id', $edition->id)->first()?->source ?? config('public_programme.source');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function initialEvents(): Collection
    {
        return collect(config('public_programme.events', []))->map(fn (array $event, int $index): array => [
            ...$event,
            'key' => substr(hash('sha256', $event['cells']), 0, 24),
            'category' => $event['category'] ?? 'sports',
            'is_published' => true,
            'version' => 0,
            'position' => $index,
        ])->values();
    }

    /** @return array<string, mixed> */
    public function updateEvent(IntramuralEdition $edition, string $key, array $attributes, int $version, User $actor): array
    {
        return DB::transaction(function () use ($edition, $key, $attributes, $version, $actor): array {
            // Serialize the first save as well as later edits. Once saved, the
            // complete programme is authoritative and defaults are never replayed.
            IntramuralEdition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $programme = PublicProgramme::where('edition_id', $edition->id)->lockForUpdate()->first();
            $events = $programme ? collect($programme->events) : $this->initialEvents();
            $index = $events->search(fn (array $event): bool => $event['key'] === $key);
            abort_if($index === false, 404);
            $before = $events[$index];
            if ((int) $before['version'] !== $version) {
                throw ValidationException::withMessages(['version' => 'Another administrator updated this schedule. Reload the Schedules page before saving again.']);
            }
            $updated = [...$before, ...$attributes, 'version' => $version + 1];
            $events[$index] = $updated;
            $programme ??= new PublicProgramme(['edition_id' => $edition->id, 'source' => config('public_programme.source')]);
            $programme->fill(['events' => $events->values()->all(), 'updated_by' => $actor->id])->save();
            app(AuditService::class)->record('public_schedule.updated', $programme,
                ['event' => $before], ['event' => $updated], actor: $actor);

            return $updated;
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forDate(IntramuralEdition $edition, string $date, Collection $factions): Collection
    {
        return $this->eventsFor($edition)->where('is_published', true)->where('date', $date)
            ->map(function (array $event) use ($factions): array {
                $category = $event['category'] ?? 'sports';
                $isCultural = $category === 'cultural';

                return [
                    'event' => $event['event'],
                    'category' => $category,
                    'sport_group' => null,
                    'sport_group_label' => null,
                    'game' => '',
                    'time' => isset($event['starts_at']) ? $this->formatTime($event['starts_at']) : $event['session'],
                    'cutoff' => isset($event['ends_at']) ? 'Until '.$this->formatTime($event['ends_at']) : null,
                    'period' => match ($event['session']) {
                        'AM' => 'Morning',
                        'PM' => 'Afternoon',
                        default => 'Evening',
                    },
                    'competitors' => $isCultural && $factions->isNotEmpty() ? $factions->pluck('name')->implode(' / ') : 'To be announced',
                    'competitor_teams' => $isCultural ? $factions->map(fn (array $faction): array => [
                        'name' => $faction['name'], 'code' => $faction['code'], 'logo_path' => $faction['logo_path'],
                    ])->all() : [],
                    'venue' => $event['venue'] ?: 'TBA',
                    'facilitator' => 'Unassigned',
                ];
            })->values();
    }

    private function formatTime(string $time): string
    {
        return Carbon::createFromFormat('!H:i', $time, 'Asia/Manila')->format('g:i A');
    }
}
