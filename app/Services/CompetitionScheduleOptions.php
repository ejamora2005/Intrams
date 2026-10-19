<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use Illuminate\Support\Collection;

class CompetitionScheduleOptions
{
    /** @return Collection<int, EditionSport> */
    public function events(IntramuralEdition $edition): Collection
    {
        $events = $edition->editionSports()
            ->whereIn('status', ['preparation', 'active'])
            ->whereHas('sport', fn ($query) => $query->where('status', 'active'))
            ->with('sport')->get();
        $currentSportIds = Sport::withoutSupersededOpenSports($events->pluck('sport'))->pluck('id')->all();

        return $events->whereIn('sport_id', $currentSportIds)->sortBy('sport.name')->values();
    }

    public function isActivity(EditionSport $event): bool
    {
        return strtoupper($event->sport->code) === 'CULT-FESTIVAL-PROGRAM';
    }

    public function isCultural(EditionSport $event): bool
    {
        return ! $this->isActivity($event) && str_starts_with(strtoupper($event->sport->code), 'CULT-');
    }

    public function defaultVenue(EditionSport $event): string
    {
        return match (true) {
            str_starts_with(strtoupper($event->sport->code), 'BASKET-') => 'MPCC',
            $event->sport->isTrackAndField() => 'Field/Oval',
            str_contains(strtoupper($event->sport->code), 'VOLLEYBALL') => 'Open Court (Volleyball/Pickleball)',
            default => 'TBA',
        };
    }

    /** @return Collection<string, array{label: string, team_id: ?int, entry_ids: array<int, int>}> */
    public function participants(EditionSport $event): Collection
    {
        if ($this->isActivity($event)) {
            return collect();
        }

        if ($this->isCultural($event)) {
            $event->loadMissing('edition.teams');

            return $event->edition->teams->where('status', 'active')->sortBy('name')
                ->mapWithKeys(fn ($team): array => ['team:'.$team->id => [
                    'label' => $team->name, 'team_id' => $team->id, 'entry_ids' => [],
                ]]);
        }

        $event->loadMissing(['athleteEntries.student', 'athleteEntries.team']);
        $entries = $event->athleteEntries->where('status', 'active')
            ->filter(fn (AthleteEntry $entry): bool => $entry->student?->status === 'active'
                && $event->sport->acceptsStudentGender($entry->student->gender)
                && (! $entry->team_id || ($entry->team?->status === 'active' && $entry->team->edition_id === $event->edition_id)));

        if ($event->participant_type === 'team') {
            return $entries->filter(fn ($entry): bool => $entry->team !== null)->groupBy('team_id')
                ->mapWithKeys(fn (Collection $group): array => ['team:'.$group->first()->team_id => [
                    'label' => $group->first()->team->name, 'team_id' => $group->first()->team_id, 'entry_ids' => [],
                ]])->sortBy('label');
        }

        if ($event->participant_type === 'dual') {
            return $entries->filter(fn ($entry): bool => filled($entry->pair_key))->groupBy('pair_key')
                ->filter(fn (Collection $group): bool => $group->count() === 2)
                ->mapWithKeys(fn (Collection $group, string $key): array => ['pair:'.$key => [
                    'label' => $group->sortBy('student.full_name')->pluck('student.full_name')->implode(' / '),
                    'team_id' => null, 'entry_ids' => $group->pluck('id')->all(),
                ]])->sortBy('label');
        }

        return $entries->mapWithKeys(fn ($entry): array => ['entry:'.$entry->id => [
            'label' => $entry->student->full_name.($entry->team ? ' — '.$entry->team->name : ''),
            'team_id' => null, 'entry_ids' => [$entry->id],
        ]])->sortBy('label');
    }
}
