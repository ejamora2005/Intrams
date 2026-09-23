<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intramural_editions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('school_year', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->unique(['name', 'school_year']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender', 20)->nullable();
            $table->string('year_level', 30)->nullable();
            $table->string('section', 100)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('intramural_editions')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['edition_id', 'code']);
        });

        Schema::create('events', function (Blueprint $table) {
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

        Schema::create('participation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('intramural_editions')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('max_sports');
            $table->unsignedSmallInteger('max_events');
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['edition_id', 'is_active']);
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['event_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('participation_rules');
        Schema::dropIfExists('events');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('sports');
        Schema::dropIfExists('students');
        Schema::dropIfExists('intramural_editions');
    }
};
