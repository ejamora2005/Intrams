<?php

namespace Database\Seeders;

use App\Models\IntramuralEdition;
use Illuminate\Database\Seeder;

class DefaultIntramuralsSeeder extends Seeder
{
    public function run(): void
    {
        // A deployment flag left enabled must not replay setup over a live event.
        if (app()->environment('production') && IntramuralEdition::query()->exists()) {
            $this->command?->warn('Production competition setup already exists. Skipping default seeding to preserve live records.');

            return;
        }

        $this->call([
            DefaultSportsSeeder::class,
            TestIntramuralsDataSeeder::class,
            LegacyIntramuralsRosterCleanupSeeder::class,
            CurrentFactionRostersSeeder::class,
        ]);
    }
}
