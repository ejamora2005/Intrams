<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teams')
            ->where('name', 'Mighty Sea Dragons')
            ->update(['name' => 'Sea Dragons', 'code' => 'sea-dragons']);
    }

    public function down(): void
    {
        DB::table('teams')
            ->where('name', 'Sea Dragons')
            ->where('code', 'sea-dragons')
            ->update(['name' => 'Mighty Sea Dragons', 'code' => 'mighty-sea-dragons']);
    }
};
