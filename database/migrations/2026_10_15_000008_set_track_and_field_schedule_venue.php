<?php

use App\Models\CompetitionSchedule;
use App\Models\Sport;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const TRACK_FIELD_CODES = [
        'RUNS', 'RUNS-MEN', 'RUNS-WOMEN',
        '100M-RUN', '100M-RUN-MEN', '100M-RUN-WOMEN',
        '200M-RUN', '200M-RUN-MEN', '200M-RUN-WOMEN',
        '400M-RUN', '400M-RUN-MEN', '400M-RUN-WOMEN',
        'RELAY-4X100', 'RELAY-4X100-MEN', 'RELAY-4X100-WOMEN',
        'RELAY-4X400', 'RELAY-4X400-MEN', 'RELAY-4X400-WOMEN',
        'THROWS', 'THROWS-MEN', 'THROWS-WOMEN',
        'SHOT-PUT', 'SHOT-PUT-MEN', 'SHOT-PUT-WOMEN',
        'DISCUS-THROW', 'DISCUS-THROW-MEN', 'DISCUS-THROW-WOMEN',
        'JAVELIN-THROW', 'JAVELIN-THROW-MEN', 'JAVELIN-THROW-WOMEN',
        'JUMP', 'JUMP-MEN', 'JUMP-WOMEN',
        'LONG-JUMP', 'LONG-JUMP-MEN', 'LONG-JUMP-WOMEN',
        'TRIPLE-JUMP', 'TRIPLE-JUMP-MEN', 'TRIPLE-JUMP-WOMEN',
        'HIGH-JUMP', 'HIGH-JUMP-MEN', 'HIGH-JUMP-WOMEN',
    ];

    public function up(): void
    {
        $sportIds = Sport::query()
            ->whereIn('code', self::TRACK_FIELD_CODES)
            ->pluck('id');

        if ($sportIds->isEmpty()) {
            return;
        }

        CompetitionSchedule::query()
            ->where(function ($query): void {
                $query->whereNull('venue')->orWhere('venue', 'TBA');
            })
            ->whereHas('editionSport', fn ($query) => $query->whereIn('sport_id', $sportIds))
            ->update(['venue' => 'Field/Oval']);
    }

    public function down(): void
    {
        // Existing venue edits cannot be safely distinguished from this default.
    }
};
