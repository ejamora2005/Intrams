<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bracket_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_sport_id')->constrained()->cascadeOnDelete();
            $table->string('bracket', 20)->default('winners');
            $table->unsignedSmallInteger('round_number');
            $table->unsignedSmallInteger('match_number');
            $table->foreignId('team_one_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team_two_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('loser_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
            $table->unique(['edition_sport_id', 'bracket', 'round_number', 'match_number'], 'bracket_match_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bracket_matches');
    }
};
