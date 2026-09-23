<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('edition_sports', function (Blueprint $table) { $table->id(); $table->foreignId('edition_id')->constrained('intramural_editions')->cascadeOnDelete(); $table->foreignId('sport_id')->constrained()->restrictOnDelete(); $table->string('status',20)->default('active'); $table->timestamps(); $table->softDeletes(); $table->unique(['edition_id','sport_id']); }); } public function down(): void { Schema::dropIfExists('edition_sports'); } };
