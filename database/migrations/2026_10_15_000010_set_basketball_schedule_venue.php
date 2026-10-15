<?php

use App\Models\CompetitionSchedule;
use App\Models\Sport;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $basketballSportIds = Sport::query()
            ->where('code', 'like', 'BASKET-%')
            ->pluck('id');

        if ($basketballSportIds->isEmpty()) {
            return;
        }

        CompetitionSchedule::query()
            ->where(function ($query): void {
                $query->whereNull('venue')->orWhere('venue', 'TBA');
            })
            ->whereHas('editionSport', fn ($query) => $query->whereIn('sport_id', $basketballSportIds))
            ->update(['venue' => 'MPCC']);
    }

    public function down(): void
    {
        // Existing venue edits cannot be safely distinguished from this default.
    }
};
