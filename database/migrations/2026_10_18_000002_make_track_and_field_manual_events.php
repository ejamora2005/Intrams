<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edition_sports') || ! Schema::hasTable('sports')) {
            return;
        }

        // Track and field is a collection of timed/measured events. It is
        // intentionally represented by the custom/manual mechanic so it never
        // creates a round-robin or elimination bracket.
        DB::table('edition_sports')
            ->join('sports', 'sports.id', '=', 'edition_sports.sport_id')
            ->where(function ($query): void {
                $trackCodes = [
                    'RUNS', '100M-RUN', '200M-RUN', '400M-RUN',
                    'RELAY-4X100', 'RELAY-4X400', 'THROWS', 'SHOT-PUT',
                    'DISCUS-THROW', 'JAVELIN-THROW', 'JUMP', 'LONG-JUMP',
                    'TRIPLE-JUMP', 'HIGH-JUMP',
                ];

                $query->whereIn('sports.code', $trackCodes)
                    ->orWhere(function ($suffixQuery) use ($trackCodes): void {
                        $suffixQuery
                            ->where(function ($codeQuery) use ($trackCodes): void {
                                foreach ($trackCodes as $code) {
                                    $codeQuery->orWhere('sports.code', $code.'-MEN')
                                        ->orWhere('sports.code', $code.'-WOMEN');
                                }
                            });
                    });
            })
            ->where('edition_sports.game_mechanic', '!=', 'custom')
            ->update(['edition_sports.game_mechanic' => 'custom']);
    }

    public function down(): void
    {
        // The previous mechanics varied by edition and cannot be restored
        // safely. Track and field remains a manual-results category.
    }
};
