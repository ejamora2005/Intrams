<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Add missing source players without replacing existing roster edits or results. */
class NewFactionPlayersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->callWith([
                SeaDragonsRosterSeeder::class,
                TerraquaticEaglesRosterSeeder::class,
                TrojanWarriorsRosterSeeder::class,
            ], ['newPlayersOnly' => true]);
        });
    }
}
