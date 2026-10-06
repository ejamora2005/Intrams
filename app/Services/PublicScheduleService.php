<?php

namespace App\Services;

use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PublicScheduleService
{
    /** @return array{currentEdition: ?IntramuralEdition, todayLabel: string, selectedDate: string, availableDates: array<int, string>, todaySchedules: Collection<int, array<string, mixed>>} */
    public function today(?string $requestedDate = null): array
    {
        $now = now();
        $selectedDate = $this->parseDate($requestedDate) ?? $now->copy();
        $currentEdition = IntramuralEdition::query()
            ->where('status', 'active')
            ->latest('starts_on')
            ->first();
        $todaySchedules = collect();
        $availableDates = [];

        if ($currentEdition) {
            $availableDates = CompetitionSchedule::query()
                ->whereIn('edition_sport_id', $currentEdition->editionSports()->select('id'))
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->orderBy('starts_at')
                ->get(['starts_at'])
                ->map(fn (CompetitionSchedule $schedule): string => $schedule->starts_at->format('Y-m-d'))
                ->unique()
                ->values()
                ->all();

            $todaySchedules = CompetitionSchedule::query()
                ->select(['id', 'edition_sport_id', 'bracket_match_id', 'title', 'starts_at', 'venue', 'coordinator_id', 'status'])
                ->whereIn('edition_sport_id', $currentEdition->editionSports()->select('id'))
                ->whereDate('starts_at', $selectedDate->toDateString())
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->with([
                    'editionSport:id,edition_id,sport_id',
                    'editionSport.sport:id,name',
                    'bracketMatch:id,match_number',
                    'coordinator:id,name',
                    'participants' => fn ($query) => $query
                        ->select(['id', 'competition_schedule_id', 'team_id', 'athlete_entry_id', 'slot'])
                        ->where('status', 'active')
                        ->orderBy('slot')
                        ->with([
                            'team:id,name,code',
                            'athleteEntry:id,student_id',
                            'athleteEntry.student:id,first_name,middle_name,last_name',
                        ]),
                ])
                ->orderBy('starts_at')
                ->get()
                ->map(function (CompetitionSchedule $schedule): array {
                    $competitors = $schedule->participants
                        ->map(fn ($participant) => $participant->team?->name
                            ?: $participant->athleteEntry?->student?->full_name)
                        ->filter()
                        ->unique()
                        ->values();
                    $competitorTeams = $schedule->participants
                        ->pluck('team')
                        ->filter()
                        ->unique('id')
                        ->values()
                        ->map(fn (Team $team): array => [
                            'name' => $team->name,
                            'code' => $team->code,
                            'logo_path' => $team->logo_path,
                        ])
                        ->all();

                    return [
                        'sport' => $schedule->editionSport->sport->name,
                        'game' => $schedule->title ?: ($schedule->bracketMatch ? 'Game '.$schedule->bracketMatch->match_number : ''),
                        'time' => $schedule->starts_at->format('g:i A'),
                        'period' => $schedule->starts_at->hour < 12 ? 'Morning' : 'Afternoon',
                        'competitors' => $competitors->isEmpty() ? 'To be announced' : $competitors->implode(' VS '),
                        'competitor_teams' => $competitorTeams,
                        'venue' => $schedule->venue ?: 'TBA',
                        'facilitator' => $schedule->coordinator?->name ?? 'Unassigned',
                    ];
                });
        }

        return [
            'currentEdition' => $currentEdition,
            'todayLabel' => $selectedDate->format('F j, Y'),
            'selectedDate' => $selectedDate->toDateString(),
            'availableDates' => $availableDates,
            'todaySchedules' => $todaySchedules,
        ];
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            return null;
        }
    }
}
