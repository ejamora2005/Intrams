<?php

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\Sport;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const EVENTS = [
        'BASKET-3X3' => ['name' => 'Basketball 3x3', 'participant_type' => 'team'],
        'BASKET-5X5' => ['name' => 'Basketball 5x5', 'participant_type' => 'team'],
    ];

    public function up(): void
    {
        $openSports = Sport::query()->whereIn('code', array_keys(self::EVENTS))->get()->keyBy('code');
        if ($openSports->isEmpty()) {
            return;
        }

        foreach ($openSports as $baseCode => $openSport) {
            $targets = [];
            foreach (['MEN' => 'male', 'WOMEN' => 'female'] as $suffix => $category) {
                $target = Sport::withTrashed()->updateOrCreate(
                    ['code' => $baseCode.'-'.$suffix],
                    [
                        'name' => self::EVENTS[$baseCode]['name'].' ('.ucfirst(strtolower($suffix)).')',
                        'participant_category' => $category,
                        'status' => 'active',
                        'is_system' => true,
                    ],
                );
                if ($target->trashed()) {
                    $target->restore();
                }
                $targets[$suffix] = $target;
            }

            foreach (EditionSport::query()->where('sport_id', $openSport->id)->get() as $editionSport) {
                $replacementEditionSports = [];
                foreach ($targets as $suffix => $targetSport) {
                    $replacementEditionSports[$suffix] = EditionSport::query()->firstOrCreate(
                        ['edition_id' => $editionSport->edition_id, 'sport_id' => $targetSport->id],
                        [
                            'participant_type' => $editionSport->participant_type,
                            'game_mechanic' => $editionSport->game_mechanic,
                            'match_rules' => $editionSport->match_rules,
                            'scoring_rules' => $editionSport->scoring_rules,
                            'rules' => $editionSport->rules,
                            'status' => $editionSport->status,
                        ],
                    );
                }

                AthleteEntry::query()->with('student')->where('edition_sport_id', $editionSport->id)->get()->each(function (AthleteEntry $entry) use ($replacementEditionSports): void {
                    $suffix = $entry->student?->gender === 'Male' ? 'MEN' : ($entry->student?->gender === 'Female' ? 'WOMEN' : null);
                    if ($suffix) {
                        $entry->update(['edition_sport_id' => $replacementEditionSports[$suffix]->id]);
                    } else {
                        $entry->update(['status' => 'inactive']);
                    }
                });
            }

            $openSport->update(['status' => 'archived']);
            EditionSport::query()->where('sport_id', $openSport->id)->update(['status' => 'archived']);
        }
    }

    public function down(): void
    {
        Sport::query()->whereIn('code', array_keys(self::EVENTS))->update(['status' => 'active']);
    }
};
