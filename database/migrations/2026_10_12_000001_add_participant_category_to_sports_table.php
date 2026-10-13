<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->string('participant_category', 20)->default('open')->after('description')->index();
            $table->string('participant_category_label', 100)->nullable()->after('participant_category');
        });
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropIndex(['participant_category']);
            $table->dropColumn(['participant_category', 'participant_category_label']);
        });
    }
};
