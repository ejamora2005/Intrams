<?php

namespace App\Services;

use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use App\Models\Team;
use Illuminate\Support\Collection;

class PublicScheduleService
{
    /** @return array{currentEdition: ?IntramuralEdition, todayLabel: string, todaySchedules: Collection<int, array<string, mixed>>} */
    public function today(): array
    {
        $now = now();
        $currentEdition = IntramuralEdition::query()
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $now)
            ->whereDate('ends_on', '>=', $now)
            ->latest('starts_on')
            ->first();
        $todaySchedules = collect();

        if ($currentEdition) {
            $todaySchedules = CompetitionSchedule::query()
                ->select(['id', 'edition_sport_id', 'bracket_match_id', 'title', 'starts_at', 'venue', 'coordinator_id', 'status'])
                ->whereIn('edition_sport_id', $currentEdition->editionSports()->select('id'))
                ->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
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
            'todayLabel' => $now->format('F j, Y'),
            'todaySchedules' => $todaySchedules,
        ];
    }
}
