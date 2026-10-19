<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The edition-flow migration removed these tables, but the coordinator
        // and event routes still use them. Create only missing tables.
        if (! Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('edition_id')->constrained('intramural_editions')->restrictOnDelete();
                $table->foreignId('sport_id')->constrained()->restrictOnDelete();
                $table->string('name');
                $table->string('code', 50);
                $table->string('competition_type', 20);
                $table->string('division', 100)->nullable();
                $table->string('venue')->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->string('result_mode', 20)->default('score');
                $table->timestamps();
                $table->unique(['edition_id', 'code']);
                $table->index(['sport_id', 'status', 'starts_at']);
            });
        }

        if (! Schema::hasTable('coordinator_assignments')) {
            Schema::create('coordinator_assignments', function (Blueprint $table): void {
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
        }

        if (! Schema::hasTable('coordinator_requests')) {
            Schema::create('coordinator_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('coordinator_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('source_event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->string('request_type', 20);
                $table->text('reason');
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // Keep any restored event responsibilities and requests on rollback.
    }
};
