<?php

use App\Models\AthleteEntry;
use App\Models\EditionSport;
use App\Models\Sport;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $openSports = Sport::query()->whereIn('code', ['VOLLEYBALL', 'MODIFIED-VOLLEYBALL'])->get();
        if ($openSports->isEmpty()) {
            return;
        }

        $replacementCodes = [
            'VOLLEYBALL-MEN' => ['name' => 'Volleyball (Men)', 'category' => 'male'],
            'VOLLEYBALL-WOMEN' => ['name' => 'Volleyball (Women)', 'category' => 'female'],
            'MODIFIED-VOLLEYBALL-MEN' => ['name' => 'Modified Volleyball (Men)', 'category' => 'male'],
            'MODIFIED-VOLLEYBALL-WOMEN' => ['name' => 'Modified Volleyball (Women)', 'category' => 'female'],
        ];

        foreach ($replacementCodes as $code => $replacement) {
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

        foreach ($openSports as $openSport) {
            $baseCode = $openSport->code;
            $targetCodes = $baseCode === 'VOLLEYBALL'
                ? ['VOLLEYBALL-MEN', 'VOLLEYBALL-WOMEN']
                : ['MODIFIED-VOLLEYBALL-MEN', 'MODIFIED-VOLLEYBALL-WOMEN'];
            foreach (EditionSport::query()->where('sport_id', $openSport->id)->get() as $editionSport) {
                $replacementEditionSports = [];
                foreach ($targetCodes as $targetCode) {
                    $targetSport = Sport::query()->where('code', $targetCode)->first();
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
                AthleteEntry::query()->with('student')->where('edition_sport_id', $editionSport->id)->get()->each(function (AthleteEntry $entry) use ($replacementEditionSports, $baseCode): void {
                    $isModified = $baseCode === 'MODIFIED-VOLLEYBALL';
                    $code = $entry->student?->gender === 'Male' ? ($isModified ? 'MODIFIED-VOLLEYBALL-MEN' : 'VOLLEYBALL-MEN') : ($entry->student?->gender === 'Female' ? ($isModified ? 'MODIFIED-VOLLEYBALL-WOMEN' : 'VOLLEYBALL-WOMEN') : null);
                    if ($code && isset($replacementEditionSports[$code])) {
                        $entry->update(['edition_sport_id' => $replacementEditionSports[$code]->id]);
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
        Sport::query()->whereIn('code', ['VOLLEYBALL', 'MODIFIED-VOLLEYBALL'])->update(['status' => 'active']);
    }
};
