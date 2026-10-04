<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultIntramuralsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultSportsSeeder::class,
            TestIntramuralsDataSeeder::class,
            IntramuralsStudentRosterSeeder::class,
        ]);
    }
}
