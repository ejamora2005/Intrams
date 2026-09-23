<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordinator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['coordinator_id', 'event_id']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('coordinator_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordinator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type', 20);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('coordinator_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->string('device_label')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('event_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20);
            $table->json('config_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['event_id', 'is_active']);
        });

        Schema::create('scoring_point_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('intramural_editions')->cascadeOnDelete();
            $table->foreignId('sport_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('placement');
            $table->decimal('points', 10, 2)->default(0);
            $table->string('medal', 20)->nullable();
            $table->timestamps();
            $table->index(['edition_id', 'sport_id', 'event_id', 'placement'], 'score_rule_lookup');
        });

        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('round_name')->nullable();
            $table->unsignedInteger('sequence');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'sequence']);
            $table->index(['event_id', 'status', 'scheduled_at']);
        });

        Schema::create('fixture_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('event_registrations')->restrictOnDelete();
            $table->string('lane_or_slot', 30);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['fixture_id', 'lane_or_slot']);
            $table->unique(['fixture_id', 'team_id']);
            $table->unique(['fixture_id', 'registration_id']);
        });

        Schema::create('result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();
            $table->unique(['fixture_id', 'revision']);
        });

        Schema::create('result_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_competitor_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 12, 3)->nullable();
            $table->unsignedSmallInteger('rank')->nullable();
            $table->decimal('points_awarded', 10, 2)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->json('details_json')->nullable();
            $table->timestamps();
            $table->unique(['result_submission_id', 'fixture_competitor_id']);
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

        Schema::create('team_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('event_registrations')->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->uuid('request_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('team_flags');
        Schema::dropIfExists('team_tallies');
        Schema::dropIfExists('result_entries');
        Schema::dropIfExists('result_submissions');
        Schema::dropIfExists('fixture_competitors');
        Schema::dropIfExists('fixtures');
        Schema::dropIfExists('scoring_point_rules');
        Schema::dropIfExists('event_scoring_rules');
        Schema::dropIfExists('coordinator_devices');
        Schema::dropIfExists('coordinator_requests');
        Schema::dropIfExists('coordinator_assignments');
    }
};
