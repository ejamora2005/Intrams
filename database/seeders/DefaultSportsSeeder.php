<?php

namespace Database\Seeders;

use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\EditionService;
use App\Services\RacketEventFormatService;
use Illuminate\Database\Seeder;

class DefaultSportsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('intramurals.default_competitions', []) as $sport) {
            $attributes = [
                'name' => $sport['name'],
                'description' => $sport['description'] ?? null,
                'status' => 'active',
                'is_system' => true,
            ];

            if (array_key_exists('participant_category', $sport)) {
                $attributes['participant_category'] = $sport['participant_category'];
                $attributes['participant_category_label'] = $sport['participant_category_label'] ?? null;
            }

            $model = Sport::withTrashed()->updateOrCreate(
                ['code' => $sport['code']],
                $attributes,
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }

        $this->ensureDefaultEdition();

        $editionService = app(EditionService::class);
        IntramuralEdition::query()->each(fn (IntramuralEdition $edition) => $editionService->syncDefaultSports($edition));
        app(RacketEventFormatService::class)->sync();
    }

    private function ensureDefaultEdition(): void
    {
        $default = config('intramurals.default_edition');
        if (! is_array($default) || empty($default['name']) || empty($default['school_year'])) {
            return;
        }

        $names = collect($default['aliases'] ?? [])
            ->push($default['name'])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $edition = IntramuralEdition::query()
            ->where('school_year', $default['school_year'])
            ->where('name', $default['name'])
            ->first();

        $edition ??= IntramuralEdition::query()
            ->where('school_year', $default['school_year'])
            ->whereIn('name', $names)
            ->first()
            ?: new IntramuralEdition;

        $edition->fill([
            'name' => $default['name'],
            'school_year' => $default['school_year'],
            'starts_on' => $default['starts_on'] ?? '2026-10-19',
            'ends_on' => $default['ends_on'] ?? '2026-10-23',
            'status' => $default['status'] ?? 'active',
        ]);
        $edition->save();
    }
}
