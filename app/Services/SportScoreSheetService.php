<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\Sport;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SportScoreSheetService
{
    /** Select a scoring discipline, independently of gender and bracket mechanic. */
    public function profile(Sport $sport): array
    {
        abort_if(str_starts_with(Str::upper($sport->code), 'CULT-'), 404);
        $code = preg_replace('/-(MEN|WOMEN)$/', '', Str::upper($sport->code));
        $name = Str::lower($sport->name);
        $key = match (true) {
            str_contains($code, 'BASKET') && str_contains($code, '3X3'), str_contains($name, 'basketball 3x3') => 'basketball-3x3',
            str_contains($code, 'BASKET'), str_contains($name, 'basketball') => 'basketball-5x5',
            str_contains($code, 'BEACH-VOLLEYBALL'), str_contains($name, 'beach volleyball') => 'beach-volleyball',
            str_contains($code, 'MODIFIED-VOLLEYBALL'), str_contains($code, 'MIXED-VOLLEYBALL') => 'school-volleyball',
            str_contains($code, 'VOLLEYBALL'), str_contains($name, 'volleyball') => 'volleyball',
            str_contains($code, 'BADMINTON-TEAM') => 'badminton-team',
            str_contains($code, 'BADMINTON'), str_contains($name, 'badminton') => 'badminton',
            str_contains($code, 'TABLE-TENNIS-TEAM') => 'table-tennis-team',
            str_contains($code, 'TABLE-TENNIS'), str_contains($name, 'table tennis') => 'table-tennis',
            str_contains($code, 'CHESS'), str_contains($name, 'chess') => 'chess',
            $code === 'BASEBALL' => 'baseball',
            $code === 'RUSSIAN-SOFTBALL' => 'russian-softball',
            $code === 'SOFTBALL' => 'softball',
            str_contains($code, 'ESPORT-ML'), str_contains($name, 'mobile legends') => 'mobile-legends',
            str_starts_with($code, 'RELAY-') => 'relay',
            $code === 'HIGH-JUMP' => 'high-jump',
            in_array($code, ['JUMP', 'LONG-JUMP', 'TRIPLE-JUMP'], true) => 'horizontal-jump',
            in_array($code, ['THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW'], true) => 'throws',
            $sport->isTrackAndField() => 'track',
            default => 'custom',
        };
        $profile = config('score_sheets.profiles.'.$key);
        $profile['key'] = $key;
        $profile['players_per_side'] = match ($key) {
            'chess' => 1,
            'basketball-3x3' => 4,
            'beach-volleyball' => 2,
            'badminton', 'table-tennis' => str_contains($code, 'DOUBLES') || $code === 'BADMINTON' ? 2 : 1,
            default => 0,
        };
        $profile['wind'] = in_array($code, ['100M-RUN', '200M-RUN', 'RUNS', 'LONG-JUMP', 'TRIPLE-JUMP', 'JUMP'], true);
        $profile['is_athletics'] = in_array($key, ['track', 'relay', 'throws', 'horizontal-jump', 'high-jump'], true);

        return $profile;
    }

    /** Resolve individual athletes' factions through membership, not only entry.team_id. */
    public function participants(EditionSport $event): Collection
    {
        return $event->athleteEntries()->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->with(['team', 'student.course', 'student.teamMembers' => fn ($query) => $query->where('edition_id', $event->edition_id)->with('team')])
            ->get()->map(function (AthleteEntry $entry): array {
                $team = $entry->team ?? $entry->student->teamMembers->first()?->team;

                return [
                    'id' => $entry->id, 'student_id' => $entry->student_id,
                    'name' => $entry->student->full_name,
                    'sheet_name' => $entry->student->last_name.'. '.Str::upper(Str::substr($entry->student->first_name, 0, 1)).'.',
                    'course' => $entry->student->course?->code ?? '',
                    'team_id' => $team?->id, 'team' => $team?->name ?? '', 'pair_key' => $entry->pair_key,
                ];
            })->sortBy(fn ($player) => $player['team'].' '.$player['name'])->values();
    }

    public function teams(Collection $participants): Collection
    {
        return $participants->filter(fn ($player) => $player['team_id'] !== null)->groupBy('team_id')
            ->map(fn ($players) => ['id' => $players->first()['team_id'], 'name' => $players->first()['team'], 'players' => $players->values()])->values();
    }
}
