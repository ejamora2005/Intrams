<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->string('title')->nullable()->after('bracket_match_id');
            $table->index(['edition_sport_id', 'starts_at', 'title'], 'schedule_default_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->dropIndex('schedule_default_lookup');
            $table->dropColumn('title');
        });
    }
};
