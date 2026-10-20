<?php

namespace App\Services;

use App\Models\EditionSport;
use Illuminate\Support\Facades\DB;

class BasketballThreeByThreeScoringService
{
    /** One-time correction for current 3x3 events and their already declared results. */
    public function sync(): void
    {
        DB::transaction(function (): void {
            $definition = config('intramurals.point_systems.sports_basketball_3x3');
            $events = EditionSport::with(['edition', 'sportResult'])
                ->whereHas('edition', fn ($query) => $query->whereIn('status', ['draft', 'active']))
                ->whereHas('sport', fn ($query) => $query->whereIn('code', $definition['codes'])->where('status', 'active'))
                ->whereIn('status', ['preparation', 'active'])->lockForUpdate()->get();
            $editions = collect();
            foreach ($events as $event) {
                $before = $event->scoring_rules ?? [];
                // A group already saved by an admin may have newer custom values.
                // Do not replace those if this one-time migration runs later.
                $alreadyConfigured = ($before['point_system'] ?? null) === 'sports_basketball_3x3'
                    && ! empty($before['placements']);
                $after = $alreadyConfigured ? $before
                    : [...$before, 'point_system' => 'sports_basketball_3x3', 'placements' => $definition['placements']];
                $points = collect($after['placements'])->keyBy('placement')->all();
                $rulesChanged = $before != $after;
                $pointsChanged = $event->sportResult !== null && $event->sportResult->points_json != $points;
                if (! $rulesChanged && ! $pointsChanged) {
                    continue;
                }
                if ($rulesChanged) {
                    $event->update(['scoring_rules' => $after]);
                    app(AuditService::class)->record('edition_sport.basketball_3x3_points_corrected', $event,
                        ['scoring_rules' => $before], ['scoring_rules' => $after]);
                }
                if ($event->sportResult !== null) {
                    $editions->put($event->edition_id, $event->edition);
                }
            }
            foreach ($editions as $edition) {
                app(StandingsService::class)->recalculateTallies($edition);
            }
        });
    }
}
