<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_sport_id')->constrained('edition_sports')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('placements_json');
            $table->json('points_json')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique('edition_sport_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_results');
    }
};
