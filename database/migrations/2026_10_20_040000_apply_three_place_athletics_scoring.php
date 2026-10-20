<?php

use App\Services\ThreePlaceScoringService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ThreePlaceScoringService::class)->sync();
    }

    public function down(): void
    {
        // Keep the approved scoring scale and declared competition history.
    }
};
