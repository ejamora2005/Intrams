<?php

use App\Services\BasketballThreeByThreeScoringService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BasketballThreeByThreeScoringService::class)->sync();
    }

    public function down(): void
    {
        // Keep the approved scoring scale and existing winner placements.
    }
};
