<?php

use App\Services\PublicProgrammeService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PublicProgrammeService::class)->importGameSchedule();
    }

    public function down(): void
    {
        // Preserve published Admin edits; this amendment never changes scores.
    }
};
