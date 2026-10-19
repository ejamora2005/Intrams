<?php

namespace App\Services;

use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PublicScheduleService
{
    /** @return array{currentEdition: ?IntramuralEdition, todayLabel: string, selectedDate: string, availableDates: array<int, string>, todaySchedules: Collection<int, array<string, mixed>>, factionRosters: Collection<int, array<string, mixed>>} */
    public function today(?string $requestedDate = null): array
    {
        $now = now();
        $selectedDate = $this->parseDate($requestedDate) ?? $now->copy();
        $currentEdition = IntramuralEdition::query()
            ->where('status', 'active')
            ->latest('starts_on')
            ->first();
        $todaySchedules = collect();
        $factionRosters = collect();
        $availableDates = [];

        if ($currentEdition) {
            $factionRosters = $currentEdition->teams()
                ->where('status', 'active')
                ->with([
                    'members' => fn ($query) => $query
                        ->where('edition_id', $currentEdition->id)
                        ->whereHas('student', fn ($studentQuery) => $studentQuery->where('status', 'active'))
                        ->with(['student.athleteEntries' => fn ($entryQuery) => $entryQuery
                            ->where('status', 'active')
                            ->whereHas('editionSport', fn ($editionSportQuery) => $editionSportQuery->where('edition_id', $currentEdition->id))
                            ->with('editionSport.sport:id,name,participant_category,participant_category_label'),
                        ]),
                ])
                ->orderBy('name')
                ->get()
                ->map(function (Team $team): array {
                    $members = $team->members
                        ->map(function ($member): ?array {
                            $student = $member->student;

                            if (! $student) {
                                return null;
                            }

                            $sports = $student->athleteEntries
                                ->map(fn ($entry) => $entry->editionSport?->sport)
                                ->filter()
                                ->unique('id')
                                ->sortBy('name')
                                ->map(fn ($sport): array => [
                                    'name' => $this->eventName($sport->name),
                                    'category' => $this->rosterCategory($sport),
                                ])
                                ->values()
                                ->all();

                            return [
                                'id' => $student->id,
                                'name' => $student->full_name,
                                'sports' => $sports,
                            ];
                        })
                        ->filter()
                        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values();

                    return [
                        'id' => $team->id,
                        'key' => Team::logoKey($team->name),
                        'name' => $team->name,
                        'code' => $team->code,
                        'logo_path' => $team->logo_path,
                        'members' => $members->all(),
                        'member_count' => $members->count(),
                    ];
                })
                ->values();

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
                ->select(['id', 'edition_sport_id', 'bracket_match_id', 'title', 'is_activity', 'starts_at', 'venue', 'coordinator_id', 'status'])
                ->whereIn('edition_sport_id', $currentEdition->editionSports()->select('id'))
                ->whereDate('starts_at', $selectedDate->toDateString())
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->with([
                    'editionSport:id,edition_id,sport_id',
                    'editionSport.sport:id,name,code',
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
                ->map(function (CompetitionSchedule $schedule) use ($factionRosters): array {
                    $sport = $schedule->editionSport->sport;
                    $isActivity = (bool) $schedule->is_activity;
                    $isCultural = ! $isActivity && str_starts_with(strtoupper((string) $sport?->code), 'CULT-');
                    $competitors = $isCultural
                        ? $factionRosters->pluck('name')->values()
                        : $schedule->participants
                            ->map(fn ($participant) => $participant->team?->name
                                ?: $participant->athleteEntry?->student?->full_name)
                            ->filter()
                            ->unique()
                            ->values();
                    $competitorTeams = $isCultural
                        ? $factionRosters->map(fn (array $faction): array => [
                            'name' => $faction['name'],
                            'code' => $faction['code'],
                            'logo_path' => $faction['logo_path'],
                        ])->all()
                        : $schedule->participants
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

                    $sportCode = strtoupper((string) $sport?->code);
                    $sportGroup = $isActivity || str_starts_with($sportCode, 'CULT-') ? null : $sport?->catalogue_group_key;

                    return [
                        'event' => $isActivity ? ($schedule->title ?: 'School Activity') : $this->eventName($sport?->name ?? 'Event'),
                        'category' => $isActivity ? 'activities' : ($isCultural ? 'cultural' : 'sports'),
                        'sport_group' => $sportGroup,
                        'sport_group_label' => $sportGroup ? $sport?->catalogue_group_label : null,
                        'game' => $isActivity ? '' : ($schedule->title ?: ($schedule->bracketMatch ? 'Game '.$schedule->bracketMatch->match_number : '')),
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
            'factionRosters' => $factionRosters,
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

    private function eventName(string $name): string
    {
        return preg_replace('/^(?:cultural|sports)\s*:\s*/i', '', $name) ?: $name;
    }

    private function rosterCategory(Sport $sport): ?string
    {
        if (in_array($sport->participant_category, ['male', 'female'], true)
            && preg_match('/\b(?:men|women)\b/i', $sport->name) === 1) {
            return null;
        }

        return $sport->participant_category_label;
    }
}
