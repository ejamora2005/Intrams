<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table): void {
            $table->boolean('is_activity')->default(false)->after('title');
        });

        DB::table('competition_schedules')
            ->where('title', 'Ruperto Run 2026')
            ->update(['is_activity' => true]);
    }

    public function down(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table): void {
            $table->dropColumn('is_activity');
        });
    }
};
