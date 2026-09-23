<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bracket_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_sport_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('identity_key', 120);
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 255);
            $table->json('athlete_entry_ids')->nullable();
            $table->timestamps();

            $table->unique(['edition_sport_id', 'identity_key'], 'bracket_competitor_identity_unique');
        });

        Schema::table('bracket_matches', function (Blueprint $table) {
            $table->foreignId('competitor_one_id')->nullable()->after('match_number')->constrained('bracket_competitors')->nullOnDelete();
            $table->foreignId('competitor_two_id')->nullable()->after('competitor_one_id')->constrained('bracket_competitors')->nullOnDelete();
            $table->foreignId('winner_competitor_id')->nullable()->after('competitor_two_id')->constrained('bracket_competitors')->nullOnDelete();
            $table->foreignId('loser_competitor_id')->nullable()->after('winner_competitor_id')->constrained('bracket_competitors')->nullOnDelete();
        });

        $teamNames = DB::table('teams')->pluck('name', 'id');
        $competitorIds = [];

        DB::table('bracket_matches')->orderBy('id')->each(function (object $match) use (&$competitorIds, $teamNames): void {
            $columns = [
                'team_one_id' => 'competitor_one_id',
                'team_two_id' => 'competitor_two_id',
                'winner_team_id' => 'winner_competitor_id',
                'loser_team_id' => 'loser_competitor_id',
            ];
            $updates = [];

            foreach ($columns as $teamColumn => $competitorColumn) {
                $teamId = $match->{$teamColumn};

                if (! $teamId) {
                    continue;
                }

                $cacheKey = $match->edition_sport_id.':'.$teamId;
                $competitorIds[$cacheKey] ??= DB::table('bracket_competitors')->insertGetId([
                    'edition_sport_id' => $match->edition_sport_id,
                    'type' => 'team',
                    'identity_key' => 'team:'.$teamId,
                    'team_id' => $teamId,
                    'label' => $teamNames[$teamId] ?? 'Team '.$teamId,
                    'athlete_entry_ids' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $updates[$competitorColumn] = $competitorIds[$cacheKey];
            }

            if ($updates !== []) {
                DB::table('bracket_matches')->where('id', $match->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bracket_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competitor_one_id');
            $table->dropConstrainedForeignId('competitor_two_id');
            $table->dropConstrainedForeignId('winner_competitor_id');
            $table->dropConstrainedForeignId('loser_competitor_id');
        });

        Schema::dropIfExists('bracket_competitors');
    }
};
