<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Import all three source rosters atomically without resetting accounts or results. */
class CurrentFactionRostersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                SeaDragonsRosterSeeder::class,
                TerraquaticEaglesRosterSeeder::class,
                TrojanWarriorsRosterSeeder::class,
            ]);
        });
    }
}
