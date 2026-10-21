<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sport_results', 'score_group')) {
            Schema::table('sport_results', function (Blueprint $table): void {
                $table->string('score_group', 20)->default('student')->after('points_json')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sport_results', 'score_group')) {
            Schema::table('sport_results', function (Blueprint $table): void {
                $table->dropIndex(['score_group']);
                $table->dropColumn('score_group');
            });
        }
    }
};
