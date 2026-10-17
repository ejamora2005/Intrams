<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Clear declared placements and tabulator submissions first so the
            // deployed edition starts with a clean scoreboard.
            foreach (['sport_results', 'competition_results'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('result_submissions')) {
                DB::table('result_submissions')->delete();
            }

            if (Schema::hasTable('bracket_matches')) {
                DB::table('bracket_matches')->update([
                    'winner_competitor_id' => null,
                    'loser_competitor_id' => null,
                    'winner_team_id' => null,
                    'loser_team_id' => null,
                    'status' => 'pending',
                ]);

                // Later elimination rounds are populated as earlier matches
                // are won; keep the first round and league pairings intact.
                DB::table('bracket_matches')
                    ->where('bracket', '!=', 'round_robin')
                    ->where('round_number', '>', 1)
                    ->update(['competitor_one_id' => null, 'competitor_two_id' => null]);
            }

            if (Schema::hasTable('team_tallies')) {
                DB::table('team_tallies')->update([
                    'gold_count' => 0,
                    'silver_count' => 0,
                    'bronze_count' => 0,
                    'points' => 0,
                    'updated_at' => null,
                ]);
            }

            // Existing editions created before the new default should use the
            // same round-robin behavior as newly created sports.
            if (Schema::hasTable('edition_sports')) {
                DB::table('edition_sports')->where('game_mechanic', 'single_elimination')->update(['game_mechanic' => 'round_robin']);
            }
        });
    }

    public function down(): void
    {
        // Declared results are intentionally not reconstructed on rollback.
    }
};
