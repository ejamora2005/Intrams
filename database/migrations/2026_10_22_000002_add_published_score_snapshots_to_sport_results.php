<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sport_results', 'published_placements_json')) {
            Schema::table('sport_results', function (Blueprint $table): void {
                $table->json('published_placements_json')->nullable()->after('placements_json');
                $table->json('published_points_json')->nullable()->after('points_json');
                $table->timestamp('score_published_at')->nullable()->after('submitted_at');
                $table->foreignId('score_published_by')->nullable()->after('score_published_at')->constrained('users')->nullOnDelete();
            });
        }

        // Existing declarations were already visible on the public landing page.
        // Seed their snapshots so this migration does not change that published state.
        DB::table('sport_results')->select(['id', 'placements_json', 'points_json', 'submitted_at', 'submitted_by'])
            ->orderBy('id')->each(function (object $result): void {
                DB::table('sport_results')->where('id', $result->id)->update([
                    'published_placements_json' => $result->placements_json,
                    'published_points_json' => $result->points_json,
                    'score_published_at' => $result->submitted_at,
                    'score_published_by' => $result->submitted_by,
                ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sport_results', 'score_published_by')) {
            Schema::table('sport_results', function (Blueprint $table): void {
                $table->dropForeign(['score_published_by']);
            });
        }

        if (Schema::hasColumn('sport_results', 'published_placements_json')) {
            Schema::table('sport_results', function (Blueprint $table): void {
                $table->dropColumn([
                    'published_placements_json',
                    'published_points_json',
                    'score_published_at',
                    'score_published_by',
                ]);
            });
        }
    }
};
