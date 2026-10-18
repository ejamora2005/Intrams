<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edition_sports', function (Blueprint $table): void {
            $table->timestamp('bracket_lineup_configured_at')->nullable()->after('status')->index();
        });

        // Brackets with recorded results already have a committed lineup. A
        // pending bracket opens empty so the administrator can assign slots.
        DB::table('edition_sports')
            ->whereExists(function ($query): void {
                $query->select(DB::raw('1'))
                    ->from('bracket_matches')
                    ->whereColumn('bracket_matches.edition_sport_id', 'edition_sports.id')
                    ->where('bracket_matches.status', 'completed');
            })
            ->update(['bracket_lineup_configured_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('edition_sports', function (Blueprint $table): void {
            $table->dropIndex(['bracket_lineup_configured_at']);
            $table->dropColumn('bracket_lineup_configured_at');
        });
    }
};
