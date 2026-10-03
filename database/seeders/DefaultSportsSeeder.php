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
        foreach (config('intramurals.default_competitions', []) as $sport) {
            $model = Sport::withTrashed()->updateOrCreate(
                ['code' => $sport['code']],
                [
                    'name' => $sport['name'],
                    'description' => $sport['description'] ?? null,
                    'status' => 'active',
                    'is_system' => true,
                ],
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }

        $editionService = app(EditionService::class);
        IntramuralEdition::query()->each(fn (IntramuralEdition $edition) => $editionService->syncDefaultSports($edition));
    }
}
