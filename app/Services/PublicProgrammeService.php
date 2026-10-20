<?php

namespace App\Services;

use App\Models\IntramuralEdition;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Read-only public timetable, independent of saved match schedules and scoring. */
class PublicProgrammeService
{
    public function appliesTo(IntramuralEdition $edition): bool
    {
        $names = collect(config('intramurals.default_edition.aliases', []))
            ->push(config('intramurals.default_edition.name'))->filter();

        return $edition->school_year === config('public_programme.school_year')
            && $names->contains($edition->name);
    }

    /** @return array<int, string> */
    public function datesFor(IntramuralEdition $edition): array
    {
        return $this->appliesTo($edition)
            ? collect(config('public_programme.events', []))->pluck('date')->unique()->sort()->values()->all()
            : [];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forDate(string $date, Collection $factions): Collection
    {
        return collect(config('public_programme.events', []))->where('date', $date)
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
