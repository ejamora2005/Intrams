<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\IntramuralEdition;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CompetitionScheduleOptions;
use App\Services\ScheduleBracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    private const SCHEDULE_STATUSES = ['scheduled', 'live', 'completed', 'cancelled'];

    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): View
    {
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:160']])['search'] ?? '');

        return view('admin.competition.index', [
            'schedules' => CompetitionSchedule::with([
                'editionSport.edition',
                'editionSport.edition.teams',
                'editionSport.sport',
                'bracketMatch.competitorOne.team', 'bracketMatch.competitorTwo.team', 'bracketMatch.teamOne', 'bracketMatch.teamTwo',
                'participants.team',
                'participants.athleteEntry.student',
            ])->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('venue', 'like', '%'.$search.'%')->orWhere('status', 'like', '%'.$search.'%')->orWhereHas('editionSport.sport', fn ($sport) => $sport->where('name', 'like', '%'.$search.'%'))->orWhereHas('participants.team', fn ($team) => $team->where('name', 'like', '%'.$search.'%'))->orWhereHas('bracketMatch.competitorOne', fn ($competitor) => $competitor->where('label', 'like', '%'.$search.'%'))->orWhereHas('bracketMatch.competitorTwo', fn ($competitor) => $competitor->where('label', 'like', '%'.$search.'%'))))->orderBy('starts_at')->orderBy('id')->paginate(20)->withQueryString(),
            'manageSchedules' => $request->routeIs('admin.schedules.matches.index'),
            'search' => $search,
        ]);
    }

    public function create(Request $request, CompetitionScheduleOptions $options): View|RedirectResponse
    {
        if ($request->integer('bracket_match_id')) {
            $existing = CompetitionSchedule::where('bracket_match_id', $request->integer('bracket_match_id'))->first();
            if ($existing) {
                return redirect()->route('admin.schedules.matches.edit', $existing);
            }
        }
        $editions = IntramuralEdition::query()->whereIn('status', ['draft', 'active'])
            ->orderByRaw("status = 'active' desc")->orderByDesc('starts_on')->get();
        $edition = $editions->firstWhere('id', old('edition_id', $request->integer('edition_id'))) ?? $editions->first();
        $events = $edition ? $options->events($edition) : collect();
        if ($events->isNotEmpty()) {
            $events->load(['edition.teams', 'athleteEntries.student', 'athleteEntries.team']);
        }
        $eventDetails = $events->mapWithKeys(fn ($event): array => [$event->id => [
            'kind' => $options->isActivity($event) ? 'activity' : ($options->isCultural($event) ? 'cultural' : 'sports'),
            'venue' => $options->defaultVenue($event),
            'participants' => $options->participants($event)->map(fn (array $participant, string $key): array => [
                'value' => $key, 'label' => $participant['label'],
            ])->values()->all(),
        ]]);

        return view('admin.competition.create', [
            'editions' => $editions, 'edition' => $edition, 'events' => $events, 'eventDetails' => $eventDetails,
            'scheduleStatuses' => ['scheduled', 'live'], 'venueOptions' => $this->venueOptions(),
            'coordinators' => User::query()->where('role', 'coordinator')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'bracketOptions' => app(ScheduleBracketService::class)->options(app(ScheduleBracketService::class)->matches($edition)),
        ]);
    }

    public function edit(CompetitionSchedule $schedule): View
    {
        $schedule->load('editionSport.edition', 'editionSport.sport', 'bracketMatch');
        $matches = app(ScheduleBracketService::class)->matches($schedule->editionSport->edition)->where('edition_sport_id', $schedule->edition_sport_id);

        return view('admin.schedules.edit-match', ['schedule' => $schedule, 'scheduleStatuses' => self::SCHEDULE_STATUSES, 'venueOptions' => $this->venueOptions(), 'bracketOptions' => app(ScheduleBracketService::class)->options($matches)]);
    }

    public function storeSchedule(Request $request, CompetitionScheduleOptions $options): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'integer', Rule::exists('intramural_editions', 'id')->whereIn('status', ['draft', 'active'])],
            'edition_sport_id' => ['required', 'integer'],
            'bracket_match_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'venue' => ['nullable', 'string', Rule::in($this->venueOptions())],
            'status' => ['required', 'in:scheduled,live'],
            'coordinator_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'coordinator')->where('status', 'active')],
            'participants' => ['nullable', 'array', 'max:1000'],
            'participants.*' => ['required', 'string', 'distinct', 'max:80'],
        ], [], [
            'edition_sport_id' => 'event', 'starts_at' => 'start time', 'ends_at' => 'end time', 'coordinator_id' => 'facilitator',
        ]);
        $edition = IntramuralEdition::findOrFail($data['edition_id']);
        $event = $options->events($edition)->firstWhere('id', $data['edition_sport_id']);
        if (! $event) {
            throw ValidationException::withMessages(['edition_sport_id' => 'Choose an available event configured for this edition.']);
        }
        if (! empty($data['bracket_match_id']) && ! BracketMatch::whereKey($data['bracket_match_id'])->where('edition_sport_id', $event->id)->exists()) {
            throw ValidationException::withMessages(['bracket_match_id' => 'Choose a bracket match for the selected event.']);
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            $date = substr($data[$field], 0, 10);
            if ($date < $edition->starts_on->format('Y-m-d') || $date > $edition->ends_on->format('Y-m-d')) {
                throw ValidationException::withMessages([$field => 'Choose a date within '.$edition->starts_on->format('M j, Y').' – '.$edition->ends_on->format('M j, Y').'.']);
            }
        }

        $isActivity = $options->isActivity($event);
        if ($isActivity && blank($data['title'] ?? null)) {
            throw ValidationException::withMessages(['title' => 'Enter the school activity name.']);
        }
        $available = $options->participants($event);
        $selected = array_values($data['participants'] ?? []);
        if (array_diff($selected, $available->keys()->all())) {
            throw ValidationException::withMessages(['participants' => 'Choose current participants registered for this event.']);
        }
        if ($options->isCultural($event)) {
            $selected = $available->keys()->all();
        }

        $schedule = DB::transaction(function () use ($data, $event, $isActivity, $options, $available, $selected): CompetitionSchedule {
            if (! empty($data['bracket_match_id'])) {
                BracketMatch::whereKey($data['bracket_match_id'])->lockForUpdate()->firstOrFail();
                if (CompetitionSchedule::where('bracket_match_id', $data['bracket_match_id'])->exists()) {
                    throw ValidationException::withMessages(['bracket_match_id' => 'This match already has a schedule. Edit its existing schedule instead.']);
                }
            }
            $schedule = CompetitionSchedule::create([
                'edition_sport_id' => $event->id,
                'bracket_match_id' => $data['bracket_match_id'] ?? null,
                'title' => trim((string) ($data['title'] ?? '')) ?: null,
                'is_activity' => $isActivity,
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
                'venue' => $data['venue'] ?? $options->defaultVenue($event),
                'status' => $data['status'], 'coordinator_id' => $data['coordinator_id'] ?? null,
            ]);
            foreach ($selected as $index => $key) {
                $participant = $available[$key];
                if ($participant['team_id']) {
                    $schedule->participants()->create(['team_id' => $participant['team_id'], 'slot' => 'P'.($index + 1), 'status' => 'active']);
                } else {
                    foreach ($participant['entry_ids'] as $entryIndex => $entryId) {
                        $schedule->participants()->create(['athlete_entry_id' => $entryId, 'slot' => 'P'.($index + 1).'-'.($entryIndex + 1), 'status' => 'active']);
                    }
                }
            }
            $after = $schedule->only($schedule->getFillable());
            $after['participants'] = $schedule->participants()->get(['team_id', 'athlete_entry_id', 'slot', 'status'])->toArray();
            $this->auditService->record('competition_schedule.created', $schedule, null, $after);

            return $schedule;
        });

        $preceding = CompetitionSchedule::query()->where(function ($query) use ($schedule): void {
            $query->where('starts_at', '<', $schedule->starts_at)->orWhere(function ($query) use ($schedule): void {
                $query->where('starts_at', $schedule->starts_at)->where('id', '<', $schedule->id);
            });
        })->count();

        return redirect()->to(route('admin.schedules.matches.index', ['page' => intdiv($preceding, 20) + 1]).'#schedule-'.$schedule->id)
            ->with('success', 'Schedule added for '.$schedule->starts_at->format('M j, Y g:i A').'.');
    }

    public function updateSchedule(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'bracket_match_id' => ['sometimes', 'nullable', 'integer', Rule::exists('bracket_matches', 'id')->where('edition_sport_id', $schedule->edition_sport_id)],
            'title' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:starts_at'],
            'venue' => ['required', 'string', Rule::in($this->venueOptions())],
            'status' => ['required', 'in:'.implode(',', self::SCHEDULE_STATUSES)],
        ]);

        foreach (['starts_at', 'ends_at'] as $field) {
            $date = substr($data[$field], 0, 10);
            if ($date < $schedule->editionSport->edition->starts_on->toDateString() || $date > $schedule->editionSport->edition->ends_on->toDateString()) {
                throw ValidationException::withMessages([$field => 'Choose a date within this edition.']);
            }
        }
        if (array_key_exists('bracket_match_id', $data) && (int) $data['bracket_match_id'] !== (int) $schedule->bracket_match_id) {
            if ($schedule->results()->exists() || $schedule->bracketMatch?->status === 'completed') {
                throw ValidationException::withMessages(['bracket_match_id' => 'The bracket link for a scored match is kept. You can still edit its dates and venue.']);
            }
            if (! empty($data['bracket_match_id']) && CompetitionSchedule::where('bracket_match_id', $data['bracket_match_id'])->whereKeyNot($schedule->id)->exists()) {
                throw ValidationException::withMessages(['bracket_match_id' => 'This match already has another schedule.']);
            }
        }
        $before = $schedule->only(['bracket_match_id', 'title', 'starts_at', 'ends_at', 'venue', 'status']);
        $schedule->update([
            'bracket_match_id' => array_key_exists('bracket_match_id', $data) ? $data['bracket_match_id'] : $schedule->bracket_match_id,
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'venue' => $data['venue'],
            'status' => $data['status'],
        ]);

        $this->auditService->record('competition_schedule.updated', $schedule, $before, $schedule->only(['bracket_match_id', 'title', 'starts_at', 'ends_at', 'venue', 'status']));

        return back()->with('success', 'Schedule updated.');
    }

    public function updateVenue(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'venue' => ['nullable', 'string', Rule::in($this->venueOptions())],
        ]);

        $before = $schedule->only(['venue']);
        $schedule->update([
            'venue' => trim((string) ($data['venue'] ?? '')) ?: 'TBA',
        ]);

        $this->auditService->record('competition_schedule.venue_updated', $schedule, $before, $schedule->only(['venue']));

        return back()->with('success', 'Schedule venue updated.');
    }

    public function destroySchedule(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
            'return_to' => ['nullable', 'in:competition,bracket'],
        ], ['confirmation.required' => 'Confirm the schedule deletion before continuing.']);
        $schedule->loadMissing('editionSport');
        $destination = ($data['return_to'] ?? null) === 'bracket' && $schedule->bracket_match_id
            ? route('admin.sports.bracket', ['sport' => $schedule->editionSport->sport_id, 'edition_id' => $schedule->editionSport->edition_id])
            : route('admin.schedules.matches.index');

        DB::transaction(function () use ($schedule): void {
            $schedule = CompetitionSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if ($schedule->results()->exists()) {
                throw ValidationException::withMessages([
                    'schedule' => 'This schedule has recorded results. Set its status to Cancelled to hide it from the public schedule.',
                ]);
            }

            $before = $schedule->only($schedule->getFillable());
            $before['participants'] = $schedule->participants()->get(['team_id', 'athlete_entry_id', 'slot', 'status'])->toArray();
            $this->auditService->record('competition_schedule.deleted', $schedule, $before);
            $schedule->delete();
        });

        return redirect()->to($destination)->with('success', 'Schedule deleted.');
    }

    private function venueOptions(): array
    {
        return collect(config('intramurals.venues', ['TBA']))
            ->merge(collect(config('public_programme_games.events', []))->pluck('venue')->filter())
            ->merge(CompetitionSchedule::whereNotNull('venue')->distinct()->pluck('venue'))->unique()
            ->filter(fn (mixed $venue): bool => is_string($venue) && $venue !== '')
            ->values()
            ->all();
    }
}
