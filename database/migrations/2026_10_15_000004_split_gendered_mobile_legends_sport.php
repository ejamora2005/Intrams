<?php

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\Sport;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $openSport = Sport::query()->where('code', 'ESPORT-ML')->first();
        if (! $openSport) {
            return;
        }

        foreach ([
            'ESPORT-ML-MEN' => ['name' => 'E-Sport (ML) (Men)', 'category' => 'male'],
            'ESPORT-ML-WOMEN' => ['name' => 'E-Sport (ML) (Women)', 'category' => 'female'],
        ] as $code => $replacement) {
            $sport = Sport::withTrashed()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $replacement['name'],
                    'participant_category' => $replacement['category'],
                    'status' => 'active',
                    'is_system' => true,
                ],
            );
            if ($sport->trashed()) {
                $sport->restore();
            }
        }

        foreach (EditionSport::query()->where('sport_id', $openSport->id)->get() as $editionSport) {
            $replacementEditionSports = [];
            foreach (['ESPORT-ML-MEN', 'ESPORT-ML-WOMEN'] as $targetCode) {
                $targetSport = Sport::query()->where('code', $targetCode)->firstOrFail();
                $replacementEditionSports[$targetCode] = EditionSport::query()->firstOrCreate(
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
                $code = match ($entry->student?->gender) {
                    'Male' => 'ESPORT-ML-MEN',
                    'Female' => 'ESPORT-ML-WOMEN',
                    default => null,
                };
                if ($code) {
                    $entry->update(['edition_sport_id' => $replacementEditionSports[$code]->id]);
                } else {
                    $entry->update(['status' => 'inactive']);
                }
            });
        }

        $openSport->update(['status' => 'archived']);
        EditionSport::query()->where('sport_id', $openSport->id)->update(['status' => 'archived']);
    }

    public function down(): void
    {
        Sport::query()->where('code', 'ESPORT-ML')->update(['status' => 'active']);
    }
};
