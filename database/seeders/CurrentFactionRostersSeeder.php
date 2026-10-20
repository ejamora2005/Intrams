<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SkipsProductionRosterImports;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Import all three source rosters atomically without resetting accounts or results. */
class CurrentFactionRostersSeeder extends Seeder
{
    use SkipsProductionRosterImports;

    public function run(): void
    {
        if ($this->skipsProductionRosterImport()) {
            return;
        }

        DB::transaction(function (): void {
            $this->call([
                SeaDragonsRosterSeeder::class,
                TerraquaticEaglesRosterSeeder::class,
                TrojanWarriorsRosterSeeder::class,
            ]);
        });
    }
}
