<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinator_requests', function (Blueprint $table) {
            $table->foreignId('source_event_id')->nullable()->after('event_id')->constrained('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coordinator_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_event_id');
        });
    }
};
