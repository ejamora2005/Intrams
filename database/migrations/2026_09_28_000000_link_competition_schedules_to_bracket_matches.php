<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->foreignId('bracket_match_id')->nullable()->unique()->after('edition_sport_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bracket_match_id');
        });
    }
};
