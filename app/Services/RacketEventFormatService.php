<?php

namespace App\Services;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class RacketEventFormatService
{
    /** Repair current formats in place, preserving registration IDs and competition history. */
    public function sync(): void
    {
        $editions = IntramuralEdition::whereIn('status', ['draft', 'active'])->get();
        if ($editions->isEmpty()) {
            return;
        }

        $definitions = collect(config('intramurals.default_competitions', []))->filter(fn (array $item): bool => in_array($item['code'], ['CHESS-MEN', 'CHESS-WOMEN'], true)
            || preg_match('/^BADMINTON-(SINGLES|DOUBLES|TEAM)-(MEN|WOMEN)$/', $item['code']) === 1);

        DB::transaction(function () use ($editions, $definitions): void {
            foreach ($definitions as $definition) {
                $sport = Sport::withTrashed()->firstOrNew(['code' => $definition['code']]);
                $sport->fill(collect($definition)->only(['name', 'participant_category'])->all());
                $sport->fill(['status' => 'active', 'is_system' => true])->save();
                if ($sport->trashed()) {
                    $sport->restore();
                }

                foreach ($editions as $edition) {
                    $event = EditionSport::withTrashed()->firstOrNew(['edition_id' => $edition->id, 'sport_id' => $sport->id]);
                    $before = $event->exists ? $event->only(['participant_type', 'status', 'deleted_at']) : null;
                    if (! $event->exists) {
                        $event->fill(['game_mechanic' => 'round_robin', 'scoring_rules' => $definition['scoring_rules']]);
                    }
                    $event->fill(['participant_type' => $definition['participant_type'], 'status' => 'active'])->save();
                    if ($event->trashed()) {
                        $event->restore();
                    }
                    $after = $event->only(['participant_type', 'status', 'deleted_at']);
                    if ($before !== $after) {
                        app(AuditService::class)->record('edition_sport.format_corrected', $event, $before, $after);
                    }

                    if ($event->participant_type !== 'team') {
                        continue;
                    }
                    foreach ($event->athleteEntries()->where('status', 'active')->with('student.teamMembers')->get() as $entry) {
                        // Prefer the player's current faction in this edition. Never guess a faction.
                        $teamId = $entry->student?->teamMembers->firstWhere('edition_id', $edition->id)?->team_id;
                        if (! $teamId || (int) $entry->team_id === (int) $teamId
                            || ! Team::where('edition_id', $edition->id)->where('status', 'active')->whereKey($teamId)->exists()) {
                            continue;
                        }
                        $old = $entry->only(['team_id']);
                        $entry->update(['team_id' => $teamId]);
                        app(AuditService::class)->record('athlete_entry.faction_restored', $entry, $old, $entry->only(['team_id']));
                    }
                }
            }
        });
    }
}
