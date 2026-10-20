<?php

namespace App\Services;

use App\Models\IntramuralEdition;
use App\Models\PublicProgramme;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Public timetable, independent of saved match schedules and scoring. */
class PublicProgrammeService
{
    public function appliesTo(IntramuralEdition $edition): bool
    {
        if (PublicProgramme::where('edition_id', $edition->id)->exists()) {
            return true;
        }

        $names = collect(config('intramurals.default_edition.aliases', []))
            ->push(config('intramurals.default_edition.name'))->filter();

        return $edition->school_year === config('public_programme.school_year')
            && $names->contains($edition->name);
    }

    /** @return array<int, string> */
    public function datesFor(IntramuralEdition $edition): array
    {
        return $this->appliesTo($edition)
            ? $this->eventsFor($edition)->where('is_published', true)->pluck('date')->unique()->sort()->values()->all()
            : [];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function eventsFor(IntramuralEdition $edition): Collection
    {
        $saved = PublicProgramme::where('edition_id', $edition->id)->first();

        return ($saved ? collect($saved->events) : $this->initialEvents())
            ->sortBy(fn (array $event): string => $event['date'].'-'
                .['AM' => 0, 'PM' => 1, 'Evening' => 2][$event['session']].'-'
                .($event['starts_at'] ?? ['AM' => '00:00', 'PM' => '12:00', 'Evening' => '18:00'][$event['session']]).'-'
                .sprintf('%06d', $event['position']))->values();
    }

    /** @return array<string, mixed>|null */
    public function sourceFor(IntramuralEdition $edition): ?array
    {
        return PublicProgramme::where('edition_id', $edition->id)->first()?->source ?? config('public_programme.source');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function initialEvents(): Collection
    {
        $events = collect(config('public_programme.events', []))->map(fn (array $event, int $index): array => [
            ...$event,
            'key' => substr(hash('sha256', $event['cells']), 0, 24),
            'category' => $event['category'] ?? 'sports',
            'is_published' => true,
            'version' => 0,
            'position' => $index,
            'games' => [],
        ])->values();

        return $this->applyGameSchedule($events);
    }

    /** Apply the supplied file to its matching public sessions only. */
    public function applyGameSchedule(Collection $events): Collection
    {
        $updates = config('public_programme_games.events', []);

        return $events->map(function (array $event) use ($updates): array {
            $patch = $updates[$event['cells'] ?? ''] ?? null;
            if (! $patch) {
                return $event;
            }
            if (isset($patch['starts_at'], $event['ends_at']) && $event['ends_at'] <= $patch['starts_at']) {
                $patch['ends_at'] = null;
            }

            return [...$event, ...$patch, 'version' => ((int) ($event['version'] ?? 0)) + 1];
        })->values();
    }

    /** A one-time amendment of saved public programmes, never sports or scores. */
    public function importGameSchedule(): int
    {
        $count = 0;
        $updateId = config('public_programme_games.id');
        foreach (PublicProgramme::with('edition')->get() as $candidate) {
            $edition = $candidate->edition;
            $names = collect(config('intramurals.default_edition.aliases', []))->push(config('intramurals.default_edition.name'));
            if (! $edition || $edition->status !== 'active' || $edition->school_year !== config('public_programme.school_year') || ! $names->contains($edition->name)) {
                continue;
            }
            $count += DB::transaction(function () use ($candidate, $updateId): int {
                IntramuralEdition::whereKey($candidate->edition_id)->lockForUpdate()->firstOrFail();
                $programme = PublicProgramme::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                $source = $programme->source ?? config('public_programme.source');
                if (in_array($updateId, $source['applied_updates'] ?? [], true)) {
                    return 0;
                }
                $before = $programme->events;
                $source['applied_updates'][] = $updateId;
                $programme->update(['events' => $this->applyGameSchedule(collect($before))->all(), 'source' => $source]);
                app(AuditService::class)->record('public_schedule.file_updated', $programme, ['events' => $before], ['events' => $programme->events, 'file' => config('public_programme_games.filename')]);

                return 1;
            });
        }

        return $count;
    }

    /** @return array<string, array{name: string, logo_path: ?string}> */
    public function teamOptions(?IntramuralEdition $edition): array
    {
        $options = collect(config('landing.teams', []))->mapWithKeys(fn (array $team): array => ['team:'.Team::logoKey($team['name']) => ['name' => $team['name'], 'logo_path' => $team['image']]])->all();
        if ($edition) {
            foreach ($edition->teams()->where('status', 'active')->orderBy('name')->get() as $team) {
                $options['team:'.Team::logoKey($team->name)] = ['name' => $team->name, 'logo_path' => $team->logo_path];
            }
        }

        return $options;
    }

    /** @return array<int, array<string, mixed>> */
    public function matchupsFor(array $games, array $teams, ?Collection $matches = null): array
    {
        $participant = function (?string $reference) use ($teams): array {
            if (isset($teams[$reference])) {
                return [...$teams[$reference], 'short' => null];
            }
            if (preg_match('/^(winner|loser):(\d+)$/', $reference ?? '', $match)) {
                return ['name' => ucfirst($match[1]).' of Game '.$match[2], 'short' => ($match[1] === 'winner' ? 'W' : 'L').$match[2], 'logo_path' => null];
            }

            return ['name' => 'To be announced', 'short' => 'TBA', 'logo_path' => null];
        };

        return collect($games)->values()->flatMap(function (array $game, int $index) use ($participant, $matches): array {
            $row = ['number' => $index + 1, 'home' => $participant($game['home'] ?? null), 'away' => $participant($game['away'] ?? null)];
            $linked = app(ScheduleBracketService::class)->linkedMatches($game, $matches ?? collect());
            if ($linked->isEmpty()) {
                return [$row];
            }
            $service = app(ScheduleBracketService::class);

            return $linked->map(fn ($match): array => [
                ...$row,
                'home' => empty($game['manual_teams']) ? $service->participant($match, 'home') : $row['home'],
                'away' => empty($game['manual_teams']) ? $service->participant($match, 'away') : $row['away'],
                'bracket_label' => $match->editionSport->sport->name.' · Round '.$match->round_number,
                'bracket_url' => route('admin.sports.bracket', ['sport' => $match->editionSport->sport_id, 'edition_id' => $match->editionSport->edition_id]),
                'schedule_url' => route('admin.schedules.matches.create', ['bracket_match_id' => $match->id, 'edition_id' => $match->editionSport->edition_id, 'edition_sport_id' => $match->edition_sport_id]),
                'scheduled_time' => $match->schedule?->starts_at->format('M j, g:i A'),
                'scheduled_venue' => $match->schedule?->venue,
                'schedule_status' => $match->schedule?->status,
                'winner' => $match->status === 'completed' ? ($match->winnerCompetitor?->label ?: $match->winner?->name) : null,
            ])->values()->all();
        })->all();
    }

    /** @return array<string, mixed> */
    public function createEvent(IntramuralEdition $edition, array $attributes, User $actor): array
    {
        return DB::transaction(function () use ($edition, $attributes, $actor): array {
            IntramuralEdition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $programme = PublicProgramme::where('edition_id', $edition->id)->lockForUpdate()->first();
            $events = $programme ? collect($programme->events) : $this->initialEvents();
            $key = bin2hex(random_bytes(12));
            if (isset($attributes['games'])) {
                app(ScheduleBracketService::class)->saveGameTimes($edition, $attributes['games'], $attributes['venue'] ?? null);
                $attributes['games'] = app(ScheduleBracketService::class)->storedGames($attributes['games']);
            }
            $event = ['venue' => null, 'starts_at' => null, 'ends_at' => null, ...$attributes, 'key' => $key, 'cells' => 'admin:'.$key, 'version' => 1, 'position' => ($events->max('position') ?? -1) + 1, 'games' => $attributes['games'] ?? []];
            $events->push($event);
            $programme ??= new PublicProgramme(['edition_id' => $edition->id, 'source' => [...config('public_programme.source'), 'applied_updates' => [config('public_programme_games.id')]]]);
            $programme->fill(['events' => $events->values()->all(), 'updated_by' => $actor->id])->save();
            app(AuditService::class)->record('public_schedule.created', $programme, null, ['event' => $event], actor: $actor);

            return $event;
        });
    }

    public function deleteEvent(IntramuralEdition $edition, string $key, int $version, User $actor): void
    {
        DB::transaction(function () use ($edition, $key, $version, $actor): void {
            IntramuralEdition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $programme = PublicProgramme::where('edition_id', $edition->id)->lockForUpdate()->first();
            $events = $programme ? collect($programme->events) : $this->initialEvents();
            $event = $events->firstWhere('key', $key);
            abort_unless($event, 404);
            if ((int) $event['version'] !== $version) {
                throw ValidationException::withMessages(['version' => 'This schedule changed. Reload Schedules before deleting it.']);
            }
            $programme ??= new PublicProgramme(['edition_id' => $edition->id, 'source' => [...config('public_programme.source'), 'applied_updates' => [config('public_programme_games.id')]]]);
            $programme->fill(['events' => $events->reject(fn ($row) => $row['key'] === $key)->values()->all(), 'updated_by' => $actor->id])->save();
            app(AuditService::class)->record('public_schedule.deleted', $programme, ['event' => $event], null, actor: $actor);
        });
    }

    /** @return array<string, mixed> */
    public function updateEvent(IntramuralEdition $edition, string $key, array $attributes, int $version, User $actor): array
    {
        return DB::transaction(function () use ($edition, $key, $attributes, $version, $actor): array {
            // Serialize the first save as well as later edits. Once saved, the
            // complete programme is authoritative and defaults are never replayed.
            IntramuralEdition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $programme = PublicProgramme::where('edition_id', $edition->id)->lockForUpdate()->first();
            $events = $programme ? collect($programme->events) : $this->initialEvents();
            $index = $events->search(fn (array $event): bool => $event['key'] === $key);
            abort_if($index === false, 404);
            $before = $events[$index];
            if ((int) $before['version'] !== $version) {
                throw ValidationException::withMessages(['version' => 'Another administrator updated this schedule. Reload the Schedules page before saving again.']);
            }
            if (isset($attributes['games'])) {
                app(ScheduleBracketService::class)->saveGameTimes($edition, $attributes['games'], $attributes['venue'] ?? $before['venue']);
                $attributes['games'] = app(ScheduleBracketService::class)->storedGames($attributes['games']);
            }
            $updated = [...$before, ...$attributes, 'version' => $version + 1];
            $events[$index] = $updated;
            $programme ??= new PublicProgramme(['edition_id' => $edition->id, 'source' => [...config('public_programme.source'), 'applied_updates' => [config('public_programme_games.id')]]]);
            $programme->fill(['events' => $events->values()->all(), 'updated_by' => $actor->id])->save();
            app(AuditService::class)->record('public_schedule.updated', $programme,
                ['event' => $before], ['event' => $updated], actor: $actor);

            return $updated;
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forDate(IntramuralEdition $edition, string $date, Collection $factions): Collection
    {
        $teams = $this->teamOptions($edition);
        $matches = app(ScheduleBracketService::class)->matches($edition);

        return $this->eventsFor($edition)->where('is_published', true)->where('date', $date)
            ->map(function (array $event) use ($factions, $teams, $matches): array {
                $category = $event['category'] ?? 'sports';
                $isCultural = $category === 'cultural';

                return [
                    'event' => $event['event'],
                    'category' => $category,
                    'sport_group' => null,
                    'sport_group_label' => null,
                    'game' => '',
                    'matchups' => $this->matchupsFor($event['games'] ?? [], $teams, $matches),
                    'time' => isset($event['starts_at']) ? $this->formatTime($event['starts_at']) : $event['session'],
                    'cutoff' => isset($event['ends_at']) ? 'Until '.$this->formatTime($event['ends_at']) : null,
                    'period' => match ($event['session']) {
                        'AM' => 'Morning',
                        'PM' => 'Afternoon',
                        default => 'Evening',
                    },
                    'competitors' => $isCultural && $factions->isNotEmpty() ? $factions->pluck('name')->implode(' / ') : 'To be announced',
                    'competitor_teams' => $isCultural ? $factions->map(fn (array $faction): array => [
                        'name' => $faction['name'], 'code' => $faction['code'], 'logo_path' => $faction['logo_path'],
                    ])->all() : [],
                    'venue' => $event['venue'] ?: 'TBA',
                    'facilitator' => 'Unassigned',
                ];
            })->values();
    }

    private function formatTime(string $time): string
    {
        return Carbon::createFromFormat('!H:i', $time, 'Asia/Manila')->format('g:i A');
    }
}
