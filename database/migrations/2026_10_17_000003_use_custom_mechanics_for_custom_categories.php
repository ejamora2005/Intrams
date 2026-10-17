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

        DB::table('edition_sports')
            ->join('sports', 'sports.id', '=', 'edition_sports.sport_id')
            ->where('sports.participant_category', 'custom')
            ->whereIn('edition_sports.game_mechanic', ['single_elimination', 'round_robin'])
            ->update(['edition_sports.game_mechanic' => 'custom']);
    }

    public function down(): void
    {
        // Existing event choices are not safely reversible.
    }
};
