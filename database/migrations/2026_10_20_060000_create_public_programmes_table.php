<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_programmes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('edition_id')->unique()->constrained('intramural_editions')->cascadeOnDelete();
            $table->json('events');
            $table->json('source')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_programmes');
    }
};
