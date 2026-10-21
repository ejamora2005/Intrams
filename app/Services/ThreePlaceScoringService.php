<?php

namespace App\Services;

use App\Models\EditionSport;
use Illuminate\Support\Facades\DB;

class ThreePlaceScoringService
{
    /** Apply the new scale without redeclaring winners or resetting competition history. */
    public function sync(): void
    {
        DB::transaction(function (): void {
            $placements = config('intramurals.point_systems.sports_athletics.placements');
            $events = EditionSport::with(['sport', 'edition', 'sportResult'])
                ->whereHas('edition', fn ($query) => $query->whereIn('status', ['draft', 'active']))
                ->whereHas('sport', fn ($query) => $query->where('status', 'active'))
                ->where('status', '!=', 'archived')->lockForUpdate()->get();
            $changedEditions = collect();
            foreach ($events as $event) {
                $rules = $event->scoring_rules ?? [];
                if (($rules['point_system_override'] ?? false) === true
                    || ($rules['non_scoring'] ?? false) === true
                    || (! $event->sport->isTrackAndField()
                        && ($rules['point_system'] ?? null) !== 'sports_athletics'
                        && count($rules['placements'] ?? []) !== 4)) {
                    continue;
                }

                $updated = [...$rules, 'placements' => $placements];
                $oldPoints = $event->sportResult?->points_json;
                $newPoints = collect($placements)->keyBy('placement')->all();
                // MySQL JSON storage reorders object keys and normalizes whole-number floats.
                $rulesChanged = $rules != $updated;
                $pointsChanged = $event->sportResult !== null && $oldPoints != $newPoints;
                if (! $rulesChanged && ! $pointsChanged) {
                    continue;
                }
                if ($rulesChanged) {
                    $event->update(['scoring_rules' => $updated]);
                }
                if ($pointsChanged) {
                    $result = $event->sportResult;
                    // Keep submitted_at, submitter, placements, and both timestamps intact.
                    // A historical fourth placement remains available for audit but earns no points.
                    $result->timestamps = false;
                    $result->points_json = $newPoints;
                    $result->saveQuietly();
                }
                app(AuditService::class)->record('edition_sport.three_place_scoring_applied', $event,
                    ['scoring_rules' => $rules, 'result_points' => $oldPoints],
                    ['scoring_rules' => $updated, 'result_points' => $event->sportResult?->points_json]);
                if ($event->sportResult !== null) {
                    $changedEditions->put($event->edition_id, $event->edition);
                }
            }
            foreach ($changedEditions as $edition) {
                app(StandingsService::class)->recalculateTallies($edition);
            }
        });
    }
}
