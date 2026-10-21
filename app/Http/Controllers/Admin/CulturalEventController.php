<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\AuditService;
use App\Services\PointSystemService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CulturalEventController extends Controller
{
    public function __construct(
        private readonly PointSystemService $pointSystems,
        private readonly StandingsService $standings,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $edition = $this->selectedEdition($request);
        $search = trim($request->string('search')->value());
        $status = $request->input('status') === 'archived' ? 'archived' : 'active';
        $system = $request->string('point_system')->value();
        $events = EditionSport::query()->with(['sport', 'sportResult'])
            ->where('edition_id', $edition?->id ?? 0)
            ->whereHas('sport', fn ($query) => $query->where('code', 'like', 'CULT-%'))
            ->where('status', $status === 'archived' ? '=' : '!=', 'archived')
            ->when($search !== '', fn ($query) => $query->whereHas('sport', fn ($sportQuery) => $sportQuery
                ->where(fn ($text) => $text->where('name', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%'))))
            ->get()->sortBy(fn (EditionSport $event): string => $event->sport->name)->values();
        if ($system !== '') {
            $events = $events->filter(fn (EditionSport $event): bool => $this->pointSystems->keyFor($event) === $system)->values();
        }

        return view('admin.cultural.index', [
            'edition' => $edition,
            'editions' => IntramuralEdition::orderByDesc('starts_on')->orderByDesc('id')->get(),
            'events' => $events,
            'search' => $search,
            'status' => $status,
            'system' => $system,
            'pointSystems' => $this->pointSystems->options($edition),
            'eventSystems' => $events->mapWithKeys(fn (EditionSport $event): array => [$event->id => $this->pointSystems->keyFor($event)]),
            'eventRules' => $events->mapWithKeys(fn (EditionSport $event): array => [$event->id => $this->standings->pointRulesFor($event)]),
            'versions' => $events->mapWithKeys(fn (EditionSport $event): array => [$event->id => $this->version($event)]),
        ]);
    }

    public function create(Request $request): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition && in_array($edition->status, ['draft', 'active'], true), 404);

        return $this->form(new EditionSport(['participant_type' => 'team', 'status' => 'preparation']), $edition);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $edition = IntramuralEdition::findOrFail($data['edition_id']);
        $this->ensureEditable($edition);
        DB::transaction(function () use ($data, $edition): void {
            $sport = Sport::create(['name' => $data['name'], 'description' => $data['description'] ?? null,
                'code' => 'CULT-CUSTOM-'.Str::upper(Str::random(10)), 'participant_category' => 'open', 'status' => 'active', 'is_system' => false]);
            $this->audit->record('sport.created', $sport, null, $sport->only($sport->getFillable()));
            $event = $edition->editionSports()->create([
                'sport_id' => $sport->id, 'participant_type' => $data['participant_type'], 'game_mechanic' => 'custom',
                'rules' => $data['rules'] ?? null, 'status' => $data['status'],
                'scoring_rules' => ['point_system' => $data['point_system'], 'point_system_override' => true,
                    'placements' => $this->pointSystems->rulesFor($edition, $data['point_system'])],
            ]);
            $this->audit->record('edition_sport.created', $event, null, $event->only($event->getFillable()));
        });

        return redirect()->route('admin.cultural.index', ['edition_id' => $edition->id])->with('success', 'Cultural event added and linked to its point system. It is available to Tabulator.');
    }

    public function edit(EditionSport $culturalEvent): View
    {
        $this->ensureCultural($culturalEvent);

        return $this->form($culturalEvent, $culturalEvent->edition);
    }

    public function update(Request $request, EditionSport $culturalEvent): RedirectResponse
    {
        $this->ensureCultural($culturalEvent);
        $data = $this->validateData($request, $culturalEvent);
        $recalculated = DB::transaction(function () use ($request, $data, $culturalEvent): bool {
            $event = EditionSport::with(['sport', 'edition', 'sportResult'])->lockForUpdate()->findOrFail($culturalEvent->id);
            $event->setRelation('sport', Sport::whereKey($event->sport_id)->lockForUpdate()->firstOrFail());
            $this->ensureEditable($event->edition);
            $this->checkVersion($request, $event);
            if ($event->status === 'archived') {
                throw ValidationException::withMessages(['status' => 'Restore this cultural event before editing it.']);
            }
            $changedSystem = $this->pointSystems->keyFor($event) !== $data['point_system'];
            $scoring = $event->scoring_rules ?? [];
            if ($changedSystem || empty($scoring['placements']) || ($scoring['non_scoring'] ?? false)) {
                $placements = $this->pointSystems->rulesFor($event->edition, $data['point_system']);
                if ($event->sportResult) {
                    if (! Hash::check((string) $request->input('admin_password'), (string) $request->user()->password)) {
                        throw ValidationException::withMessages(['admin_password' => 'Enter your admin password to change points for a declared event.']);
                    }
                    $places = array_column($placements, 'placement');
                    foreach ($event->sportResult->placements_json ?? [] as $place => $winner) {
                        if (filled($winner) && ! in_array((int) $place, $places, true)) {
                            throw ValidationException::withMessages(['point_system' => 'This point system cannot represent every declared placement. Choose a system with all recorded places.']);
                        }
                    }
                }
                $scoring = [...$scoring, 'placements' => $placements, 'non_scoring' => false];
            }
            $scoring = [...$scoring, 'point_system' => $data['point_system'], 'point_system_override' => true];
            $beforeSport = $event->sport->only(['name', 'description']);
            $event->sport->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
            $this->audit->record('sport.updated', $event->sport, $beforeSport, $event->sport->only(['name', 'description']));
            $before = $event->only(['participant_type', 'rules', 'status', 'scoring_rules']);
            $event->update(['participant_type' => $data['participant_type'], 'rules' => $data['rules'] ?? null, 'status' => $data['status'], 'scoring_rules' => $scoring]);
            $this->audit->record('edition_sport.updated', $event, $before, $event->only(array_keys($before)));
            $pointsChanged = ($before['scoring_rules']['placements'] ?? []) != ($scoring['placements'] ?? [])
                || ($before['scoring_rules']['non_scoring'] ?? false) !== ($scoring['non_scoring'] ?? false);
            if ($pointsChanged && $event->sportResult) {
                $this->standings->recalculateTallies($event->edition);
            }

            return $pointsChanged && $event->sportResult !== null;
        });

        return redirect()->route('admin.cultural.index', ['edition_id' => $culturalEvent->edition_id])
            ->with('success', $recalculated ? 'Cultural event updated. Awarded points and standings were recalculated; winners and declaration time were kept.' : 'Cultural event updated.');
    }

    public function destroy(Request $request, EditionSport $culturalEvent): RedirectResponse
    {
        $this->ensureCultural($culturalEvent);
        DB::transaction(function () use ($request, $culturalEvent): void {
            $event = EditionSport::with(['sport', 'edition'])->lockForUpdate()->findOrFail($culturalEvent->id);
            $this->ensureEditable($event->edition);
            $this->checkVersion($request, $event);
            if ($event->sportResult()->exists() || $event->schedules()->whereHas('results')->exists()
                || BracketMatch::where('edition_sport_id', $event->id)->where('status', 'completed')->exists()) {
                throw ValidationException::withMessages(['event' => 'This event has declared results and cannot be archived. Its winners and points must be retained.']);
            }
            $before = ['status' => $event->status];
            $event->update(['status' => 'archived']);
            $this->audit->record('edition_sport.archived', $event, $before, ['status' => 'archived']);
        });

        return redirect()->route('admin.cultural.index', ['edition_id' => $culturalEvent->edition_id])->with('success', 'Cultural event archived. You can restore it from Archived events.');
    }

    public function restore(Request $request, EditionSport $culturalEvent): RedirectResponse
    {
        $this->ensureCultural($culturalEvent);
        DB::transaction(function () use ($request, $culturalEvent): void {
            $event = EditionSport::with(['sport', 'edition'])->lockForUpdate()->findOrFail($culturalEvent->id);
            $this->ensureEditable($event->edition);
            $this->checkVersion($request, $event);
            abort_unless($event->status === 'archived', 404);
            $before = $event->only(['status', 'scoring_rules']);
            $system = $this->pointSystems->keyFor($event);
            if ($system && ! $event->sportResult()->exists() && $this->pointSystems->eventsFor($event->edition, $system)->isNotEmpty()) {
                $event->scoring_rules = [...($event->scoring_rules ?? []), 'placements' => $this->pointSystems->rulesFor($event->edition, $system)];
            }
            $event->status = 'preparation';
            $event->save();
            $this->audit->record('edition_sport.restored', $event, $before, $event->only(['status', 'scoring_rules']));
        });

        return redirect()->route('admin.cultural.index', ['edition_id' => $culturalEvent->edition_id])->with('success', 'Cultural event restored.');
    }

    private function form(EditionSport $event, IntramuralEdition $edition): View
    {
        return view('admin.cultural.form', ['event' => $event, 'edition' => $edition, 'pointSystems' => $this->pointSystems->options($edition, $event->exists ? $event : null),
            'selectedSystem' => $event->exists ? $this->pointSystems->keyFor($event) : 'cultural_standard_10_7_5',
            'version' => $event->exists ? $this->version($event) : null]);
    }

    private function validateData(Request $request, ?EditionSport $event = null): array
    {
        $request->merge(['name' => Str::title(Str::squish(strip_tags((string) $request->input('name'))))]);

        return $request->validate([
            'edition_id' => [$event ? 'sometimes' : 'required', 'integer', 'exists:intramural_editions,id', ...($event ? [Rule::in([$event->edition_id])] : [])],
            'name' => ['required', 'string', 'max:255', Rule::unique('sports', 'name')->ignore($event?->sport_id)],
            'description' => ['nullable', 'string', 'max:2000'], 'rules' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:preparation,active'], 'participant_type' => ['required', 'in:team,individual,dual'],
            'point_system' => ['required', Rule::in(array_keys(config('intramurals.point_systems', [])))],
            'admin_password' => ['nullable', 'string', 'max:255'],
            ...($event ? ['event_version' => ['required', 'string', 'size:64']] : []),
        ]);
    }

    private function selectedEdition(Request $request): ?IntramuralEdition
    {
        return $request->filled('edition_id') ? IntramuralEdition::findOrFail($request->integer('edition_id'))
            : IntramuralEdition::whereIn('status', ['draft', 'active'])->orderByDesc('starts_on')->orderByDesc('id')->first();
    }

    private function ensureCultural(EditionSport $event): void
    {
        $event->loadMissing(['sport', 'edition', 'sportResult']);
        abort_unless($event->sport && str_starts_with($event->sport->code, 'CULT-'), 404);
    }

    private function ensureEditable(IntramuralEdition $edition): void
    {
        if (! in_array($edition->status, ['draft', 'active'], true)) {
            throw ValidationException::withMessages(['edition_id' => 'This edition is archived. Its cultural events are read-only.']);
        }
    }

    private function version(EditionSport $event): string
    {
        return hash('sha256', json_encode([$event->only(['participant_type', 'rules', 'status', 'scoring_rules']),
            $event->sport?->only(['name', 'description', 'status'])], JSON_THROW_ON_ERROR));
    }

    private function checkVersion(Request $request, EditionSport $event): void
    {
        $version = $request->input('event_version');
        if (! is_string($version) || ! hash_equals($this->version($event), $version)) {
            throw ValidationException::withMessages(['event_version' => 'This cultural event was changed by another user. Reload the page and review the current details before saving.']);
        }
    }
}
