<?php

use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaultEdition = config('intramurals.default_edition', []);
        $editionNames = collect($defaultEdition['aliases'] ?? [])
            ->push($defaultEdition['name'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $edition = IntramuralEdition::query()
            ->whereIn('name', $editionNames)
            ->when(
                filled($defaultEdition['school_year'] ?? null),
                fn ($query) => $query->where('school_year', $defaultEdition['school_year']),
            )
            ->first()
            ?? IntramuralEdition::query()->where('status', 'active')->latest('starts_on')->first();

        if ($edition === null) {
            return;
        }

        $editionSports = $edition->editionSports()
            ->with('sport:id,code')
            ->get()
            ->keyBy(fn ($editionSport): string => (string) $editionSport->sport?->code);

        foreach (config('intramurals.default_schedules', []) as $schedule) {
            $editionSport = $editionSports->get($schedule['code'] ?? '');
            if ($editionSport === null) {
                continue;
            }

            $startsAt = $edition->starts_on
                ->copy()
                ->addDays(max(1, (int) ($schedule['day'] ?? 1)) - 1)
                ->setTimeFromTimeString($schedule['starts_at'] ?? '08:00');
            $endsAt = $edition->starts_on
                ->copy()
                ->addDays(max(1, (int) ($schedule['day'] ?? 1)) - 1)
                ->setTimeFromTimeString($schedule['ends_at'] ?? '10:00');

            $competitionSchedule = CompetitionSchedule::query()->firstOrCreate(
                [
                    'edition_sport_id' => $editionSport->id,
                    'starts_at' => $startsAt,
                    'title' => $schedule['title'] ?? null,
                ],
                [
                    'ends_at' => $endsAt->greaterThan($startsAt) ? $endsAt : $startsAt->copy()->addHours(2),
                    'venue' => $schedule['venue'] ?? 'TBA',
                    'is_activity' => (bool) ($schedule['is_activity'] ?? false),
                    'status' => $schedule['status'] ?? 'scheduled',
                ],
            );

            if (array_key_exists('is_activity', $schedule)) {
                $competitionSchedule->update(['is_activity' => (bool) $schedule['is_activity']]);
            }
        }
    }

    public function down(): void
    {
        // Schedule entries are retained so published programme history is not removed by a rollback.
    }
};
