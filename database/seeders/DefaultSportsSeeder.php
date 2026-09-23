<?php

namespace Database\Seeders;

use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\EditionService;
use Illuminate\Database\Seeder;

class DefaultSportsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Basketball 3x3', 'code' => 'BASKET-3X3'],
            ['name' => 'Basketball 5x5', 'code' => 'BASKET-5X5'],
            ['name' => 'Volleyball', 'code' => 'VOLLEYBALL'],
            ['name' => 'Badminton', 'code' => 'BADMINTON'],
            ['name' => 'Table Tennis', 'code' => 'TABLE-TENNIS'],
            ['name' => 'Baseball', 'code' => 'BASEBALL'],
            ['name' => 'Softball', 'code' => 'SOFTBALL'],
            ['name' => 'Russian Softball', 'code' => 'RUSSIAN-SOFTBALL'],
        ] as $sport) {
            Sport::withTrashed()->updateOrCreate(['code' => $sport['code']], [...$sport, 'status' => 'active', 'is_system' => true, 'deleted_at' => null]);
        }

        $editionService = app(EditionService::class);
        IntramuralEdition::query()->each(fn (IntramuralEdition $edition) => $editionService->syncDefaultSports($edition));
    }
}
