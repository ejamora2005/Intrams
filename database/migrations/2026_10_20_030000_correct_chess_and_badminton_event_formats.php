<?php

use App\Services\RacketEventFormatService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RacketEventFormatService::class)->sync();
    }

    public function down(): void
    {
        // Preserve the corrected formats, registrations, and recorded results.
    }
};
