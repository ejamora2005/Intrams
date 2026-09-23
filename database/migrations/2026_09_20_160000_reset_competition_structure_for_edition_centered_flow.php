<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration deliberately removes the legacy event-registration model.
        Schema::dropIfExists('result_entries');
        Schema::dropIfExists('result_submissions');
        Schema::dropIfExists('fixture_competitors');
        Schema::dropIfExists('fixtures');
        Schema::dropIfExists('team_flags');
        Schema::dropIfExists('team_tallies');
        Schema::dropIfExists('scoring_point_rules');
        Schema::dropIfExists('event_scoring_rules');
        Schema::dropIfExists('coordinator_assignments');
        Schema::dropIfExists('coordinator_requests');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('participation_rules');
        Schema::dropIfExists('events');
        Schema::dropIfExists('edition_sports');

        Schema::create('edition_sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('intramural_editions')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('participant_type', 20); // team, dual, individual
            $table->string('game_mechanic', 40); // single_elimination, double_elimination, round_robin, custom
            $table->json('match_rules')->nullable();
            $table->json('scoring_rules')->nullable();
            $table->text('rules')->nullable();
            $table->string('status', 20)->default('preparation')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['edition_id', 'sport_id']);
        });

        Schema::create('athlete_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('pair_key')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['edition_sport_id', 'student_id']);
        });

        Schema::create('competition_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_sport_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('venue')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['edition_sport_id', 'starts_at']);
        });

        Schema::create('schedule_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('athlete_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('slot', 30);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['competition_schedule_id', 'slot'], 'schedule_slot_unique');
            $table->unique(['competition_schedule_id', 'team_id'], 'schedule_team_unique');
            $table->unique(['competition_schedule_id', 'athlete_entry_id'], 'schedule_entry_unique');
        });

        Schema::create('competition_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_schedule_id')->constrained()->cascadeOnDelete();
            $table->json('result_data');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('team_tallies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('intramural_editions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('gold_count')->default(0);
            $table->unsignedInteger('silver_count')->default(0);
            $table->unsignedInteger('bronze_count')->default(0);
            $table->decimal('points', 12, 2)->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['edition_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_tallies');
        Schema::dropIfExists('competition_results');
        Schema::dropIfExists('schedule_participants');
        Schema::dropIfExists('competition_schedules');
        Schema::dropIfExists('athlete_entries');
        Schema::dropIfExists('edition_sports');
    }
};
