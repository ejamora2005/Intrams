<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportRequest;
use App\Http\Requests\UpdateSportRequest;
use App\Models\Sport;
use App\Models\IntramuralEdition;
use App\Models\EditionSport;
use App\Models\Course;
use App\Models\Student;
use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\AuditService;
use App\Services\BracketService;
use App\Services\SportService;
use App\Services\ScoreSheetImageOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SportController extends Controller
{
    public function __construct(private readonly SportService $service, private readonly BracketService $bracketService) {}
    public function index(Request $request): View
    {
        return $this->catalogue($request, false);
    }

    public function cultural(Request $request): View
    {
        return $this->catalogue($request, true);
    }

    private function catalogue(Request $request, bool $cultural): View
    {
        $status = $request->string('status')->value() ?: 'active';
        $edition = $this->selectedEdition($request);
        $editionId = $edition?->id;
        $sports = Sport::query()
            ->when(
                $cultural,
                fn ($query) => $query->where('code', 'like', 'CULT-%'),
                fn ($query) => $query->where('code', 'not like', 'CULT-%'),
            )
            ->when($edition, fn ($query) => $query->whereHas('editionSports', fn ($configuration) => $configuration->where('edition_id', $edition->id)))
            ->withCount(['editionSports as edition_sports_count' => fn ($query) => $edition ? $query->where('edition_id', $edition->id) : $query])
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(60)
            ->withQueryString();
        $editions = IntramuralEdition::query()->orderByDesc('starts_on')->orderByDesc('id')->get();

        return view('admin.sports.index', [
            'sports' => $sports,
            'status' => $status,
            'edition' => $edition,
            'editionId' => $editionId,
            'editions' => $editions,
            'title' => $cultural ? 'Cultural' : 'Sports',
            'subtitle' => $cultural
                ? 'Manage cultural scoring events and bonus awards.'
                : 'Manage the sport catalogue, mechanics, and participants.',
            'listLabel' => $cultural ? 'Cultural events list' : 'Sports list',
            'emptyMessage' => $cultural ? 'No cultural events found.' : 'No sports found.',
            'showAddButton' => ! $cultural,
            'cardTarget' => $cultural ? 'edit' : 'bracket',
        ]);
    }

    public function create(Request $request): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 422, 'Create an edition before adding sports.');

        return view('admin.sports.create', ['sport' => new Sport(), 'editionSport' => null, 'edition' => $edition]);
    }

    public function store(StoreSportRequest $request): RedirectResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 422, 'Create an edition before adding sports.');
        $sport = $this->service->create($request->validated());
        EditionSport::query()->firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => $request->input('participant_type', 'team'), 'game_mechanic' => $request->input('game_mechanic', 'single_elimination'), 'rules' => $request->input('rules'), 'status' => $request->input('configuration_status', 'preparation')]);

        return redirect()->route('admin.sports.index', ['edition_id' => $edition->id])->with('success', 'Sport created for '.$edition->name.'.');
    }

    public function edit(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        $editionSport = $edition ? EditionSport::firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => 'team', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']) : null;

        return view('admin.sports.edit', compact('sport', 'editionSport', 'edition'));
    }

    public function update(UpdateSportRequest $request, Sport $sport): RedirectResponse
    {
        $this->service->update($sport, $request->validated());
        $data = $request->validate(['participant_type' => ['required', 'in:team,dual,individual'], 'game_mechanic' => ['required', 'in:single_elimination,double_elimination,round_robin,custom'], 'rules' => ['nullable', 'string', 'max:5000'], 'configuration_status' => ['required', 'in:preparation,active,archived']]);
        $edition = $this->selectedEdition($request);
        if ($edition) {
            $editionSport = EditionSport::firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => 'team', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']);
            $editionSport->update(['participant_type' => $data['participant_type'], 'game_mechanic' => $data['game_mechanic'], 'rules' => $data['rules'], 'status' => $data['configuration_status']]);
        }

        return redirect()->route('admin.sports.index', ['edition_id' => $edition?->id])->with('success', 'Sport and mechanics updated.');
    }
    public function destroy(Sport $sport): RedirectResponse { $this->service->archive($sport); return redirect()->route('admin.sports.index')->with('success', 'Sport archived.'); }
    public function restore(int $sport): RedirectResponse { $sport = Sport::withTrashed()->findOrFail($sport); $this->service->restore($sport); return redirect()->route('admin.sports.index', ['status' => 'archived'])->with('success', 'Sport restored.'); }
    public function participants(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);

        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->with(['athleteEntries.student', 'athleteEntries.team'])
            ->firstOrFail();
        $activeEntries = $editionSport->athleteEntries->where('status', 'active')->values();
        $teamGroups = $activeEntries
            ->filter(fn ($entry) => $entry->team !== null)
            ->groupBy('team_id')
            ->sortBy(fn ($entries) => $entries->first()->team->name)
            ->values();
        $participants = $activeEntries->groupBy(fn ($entry) => $entry->team?->name ?? 'Individual participants');

        $competitors = match ($editionSport->participant_type) {
            'team' => $teamGroups->map(fn ($entries) => ['name' => $entries->first()->team->name, 'detail' => $entries->count().' registered athlete(s)'])->values(),
            'dual' => $activeEntries->groupBy(fn ($entry) => $entry->pair_key ?: 'entry-'.$entry->id)->map(fn ($entries) => ['name' => $entries->pluck('student.full_name')->implode(' / '), 'detail' => $entries->count().' paired athlete(s)'])->values(),
            default => $activeEntries->map(fn ($entry) => ['name' => $entry->student->full_name, 'detail' => $entry->student->student_number])->values(),
        };

        $bracketRounds = collect();
        if (in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination'], true) && $competitors->isNotEmpty()) {
            $bracketSize = 2 ** (int) ceil(log(max($competitors->count(), 2), 2));
            $slots = $competitors->pluck('name')->pad($bracketSize, 'Bye')->all();
            $bracketRounds->push($slots);

            for ($round = 2; count($slots) > 1; $round++) {
                $slots = array_fill(0, (int) (count($slots) / 2), 'Winner of round '.($round - 1));
                $bracketRounds->push($slots);
            }
        }

        return view('admin.sports.participants', compact('sport', 'edition', 'editionSport', 'participants', 'teamGroups', 'competitors', 'bracketRounds'));
    }

    public function bracket(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->firstOrFail();
        $this->bracketService->initialize($editionSport);
        $matches = BracketMatch::query()
            ->where('edition_sport_id', $editionSport->id)
            ->with(['competitorOne', 'competitorTwo', 'winnerCompetitor', 'schedule'])
            ->orderBy('bracket')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get()
            ->groupBy('bracket');
        return view('admin.sports.bracket', compact('sport', 'edition', 'editionSport', 'matches'));
    }

    public function basketballScoreSheet(Request $request, Sport $sport): View
    {
        return view('admin.sports.basketball-score-sheet', $this->basketballScoreSheetContext($request, $sport));
    }

    public function volleyballScoreSheet(Request $request, Sport $sport): View
    {
        return view('admin.sports.volleyball-score-sheet', $this->volleyballScoreSheetContext($request, $sport));
    }

    public function downloadBasketballScoreSheet(Request $request, Sport $sport): Response
    {
        $request->validate([
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'format' => ['required', 'in:docx,xlsx,pdf'],
            'preview_image' => ['required', 'file', 'image', 'mimetypes:image/png', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        $context = $this->basketballScoreSheetContext($request, $sport, false);
        $edition = $context['edition'];
        $format = $request->input('format');
        $mime = match ($format) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pdf' => 'application/pdf',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
        $preview = $request->file('preview_image');
        [$width, $height] = getimagesize($preview->getRealPath());
        abort_if(abs($width / $height - 210 / 297) > .005, 422, 'The capture must include the full A4 paper. Refresh the page and try again.');
        if ($format === 'pdf') {
            $workbook = app(\App\Services\ScoreSheetPdfService::class)->create(file_get_contents($preview->getRealPath()), $width, $height);
        } else {
            $workbook = app(ScoreSheetImageOfficeService::class)->create($format, file_get_contents($preview->getRealPath()));
        }
        app(AuditService::class)->record('basketball_score_sheet.downloaded', $sport, null, ['edition_id' => $edition->id, 'format' => $format]);

        return response($workbook, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="basketball-score-sheet-'.$edition->id.'.'.$format.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadVolleyballScoreSheet(Request $request, Sport $sport): Response
    {
        $request->validate([
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'format' => ['required', 'in:docx,xlsx,pdf'],
            'preview_image' => ['required', 'file', 'image', 'mimetypes:image/png', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        $context = $this->volleyballScoreSheetContext($request, $sport, false);
        $edition = $context['edition'];
        $format = $request->input('format');
        $mime = match ($format) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pdf' => 'application/pdf',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
        $preview = $request->file('preview_image');
        [$width, $height] = getimagesize($preview->getRealPath());
        abort_if(abs($width / $height - 297 / 210) > .005, 422, 'The capture must include the full A4 landscape paper. Refresh the page and try again.');
        $content = $format === 'pdf'
            ? app(\App\Services\ScoreSheetPdfService::class)->create(file_get_contents($preview->getRealPath()), $width, $height, 'landscape')
            : app(ScoreSheetImageOfficeService::class)->create($format, file_get_contents($preview->getRealPath()), 'landscape', 'Volleyball score sheet');
        app(AuditService::class)->record('volleyball_score_sheet.downloaded', $sport, null, ['edition_id' => $edition->id, 'format' => $format]);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="volleyball-score-sheet-'.$edition->id.'.'.$format.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function recordBracketResult(Request $request, Sport $sport, BracketMatch $match): RedirectResponse
    {
        abort_unless($match->editionSport->sport_id === $sport->id, 404);
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null && $match->editionSport->edition_id === $edition->id, 404);
        $data = $request->validate([
            'winner_competitor_id' => ['nullable', 'integer', 'exists:bracket_competitors,id'],
            'winner_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);
        abort_if(empty($data['winner_competitor_id']) && empty($data['winner_team_id']), 422, 'Choose the winning competitor.');
        $winner = ! empty($data['winner_competitor_id'])
            ? BracketCompetitor::query()->findOrFail($data['winner_competitor_id'])
            : BracketCompetitor::query()
                ->where('edition_sport_id', $match->edition_sport_id)
                ->where('type', 'team')
                ->where('team_id', $data['winner_team_id'])
                ->firstOrFail();
        $this->bracketService->record($match, $winner);
        return redirect()->route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id])->with('success', $winner->label.' was declared the winner.');
    }

    public function scheduleBracketMatch(Request $request, Sport $sport, BracketMatch $match): RedirectResponse
    {
        abort_unless($match->editionSport->sport_id === $sport->id, 404);
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null && $match->editionSport->edition_id === $edition->id, 404);
        abort_unless($match->status === 'pending' && $match->competitor_one_id && $match->competitor_two_id, 422, 'Only a ready bracket match can be scheduled.');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$edition->starts_on->format('Y-m-d'), 'before_or_equal:'.$edition->ends_on->format('Y-m-d')],
            'period' => ['required', 'in:morning,afternoon'],
            'venue' => ['nullable', 'string', 'max:120'],
        ]);
        $startsAt = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.($data['period'] === 'morning' ? '08:00' : '13:00'));
        $venue = trim((string) ($data['venue'] ?? '')) ?: 'TBA';
        $match->loadMissing(['competitorOne', 'competitorTwo']);

        $schedule = DB::transaction(function () use ($match, $startsAt, $venue): CompetitionSchedule {
            $schedule = CompetitionSchedule::query()->updateOrCreate(
                ['bracket_match_id' => $match->id],
                [
                    'edition_sport_id' => $match->edition_sport_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHours(2),
                    'venue' => $venue,
                    'status' => 'scheduled',
                ]
            );
            $schedule->participants()->delete();

            foreach ([['competitor' => $match->competitorOne, 'slot' => 'A'], ['competitor' => $match->competitorTwo, 'slot' => 'B']] as $side) {
                if ($side['competitor']->team_id) {
                    $schedule->participants()->create(['team_id' => $side['competitor']->team_id, 'slot' => $side['slot'], 'status' => 'active']);
                    continue;
                }

                foreach ($side['competitor']->athlete_entry_ids ?? [] as $index => $athleteEntryId) {
                    $schedule->participants()->create(['athlete_entry_id' => $athleteEntryId, 'slot' => $side['slot'].($index + 1), 'status' => 'active']);
                }
            }

            return $schedule;
        });

        app(AuditService::class)->record('bracket_match.scheduled', $schedule, null, $schedule->only($schedule->getFillable()));

        return redirect()
            ->route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id])
            ->with('success', 'Game '.$match->match_number.' scheduled for '.$startsAt->format('M j, Y').' '.ucfirst($data['period']).' at '.$venue.'.');
    }

    public function resetBracket(Request $request, Sport $sport): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:RESET']]);
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->firstOrFail();

        $this->bracketService->reset($editionSport);

        return redirect()
            ->route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id])
            ->with('success', 'Bracket reset. Athlete registrations and team rosters were not changed.');
    }

    public function assignParticipants(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()->where('edition_id', $edition->id)->where('sport_id', $sport->id)->firstOrFail();
        $courseId = $request->integer('course_id') ?: null;
        $teamId = $request->integer('team_id') ?: null;
        $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);
        $teams = Team::query()->where('edition_id', $edition->id)->where('status', 'active')->orderBy('name')->get();

        $studentQuery = Student::query()
            ->where('status', 'active')
            ->with('course')
            ->whereDoesntHave('athleteEntries', fn ($entryQuery) => $entryQuery->where('edition_sport_id', $editionSport->id))
            ->when($requiresTeam && ! $teamId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->when($teamId, fn ($query) => $query->whereHas('teamMembers', fn ($memberQuery) => $memberQuery->where('team_id', $teamId)))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $students = $editionSport->participant_type === 'dual'
            ? $studentQuery->get()
            : $studentQuery->paginate(30)->withQueryString();

        return view('admin.sports.assign-participants', ['sport' => $sport, 'edition' => $edition, 'editionSport' => $editionSport, 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(), 'teams' => $teams, 'students' => $students, 'courseId' => $courseId, 'teamId' => $teamId, 'requiresTeam' => $requiresTeam]);
    }

    public function storeParticipants(Request $request, Sport $sport): RedirectResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()->where('edition_id', $edition->id)->where('sport_id', $sport->id)->firstOrFail();
        $data = $request->validate(['team_id' => ['nullable', 'exists:teams,id'], 'student_ids' => ['required', 'array', 'min:1'], 'student_ids.*' => ['integer', 'distinct', 'exists:students,id']]);
        $team = !empty($data['team_id']) ? Team::findOrFail($data['team_id']) : null;
        $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);
        abort_if($requiresTeam && (! $team || $team->edition_id !== $edition->id), 422, 'Choose a team from this edition.');
        abort_if(! $requiresTeam && $team, 422, 'Individual sports do not use a team assignment.');
        abort_if($editionSport->participant_type === 'dual' && count($data['student_ids']) !== 2, 422, 'Select exactly two students for this dual sport.');
        $students = Student::query()->where('status', 'active')->whereIn('id', $data['student_ids'])->get();
        abort_unless($students->count() === count($data['student_ids']), 422, 'Only active students can be assigned.');

        if ($requiresTeam) {
            $roster = TeamMember::query()->where('team_id', $team->id)->pluck('student_id');
            abort_unless($students->every(fn ($student) => $roster->contains($student->id)), 422, 'Each selected student must be on the chosen team roster.');
        }

        abort_if(
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'completed')->exists(),
            422,
            'Participant registrations cannot be changed after a bracket result has been recorded.'
        );

        DB::transaction(function () use ($students, $editionSport, $team): void {
            abort_if(AthleteEntry::query()->where('edition_sport_id', $editionSport->id)->whereIn('student_id', $students->pluck('id'))->exists(), 422, 'One or more selected students are already registered.');
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->delete();
            BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->delete();
            $pairKey = $editionSport->participant_type === 'dual' ? (string) Str::uuid() : null;
            foreach ($students as $student) {
                $entry = AthleteEntry::query()->create(['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $team?->id, 'pair_key' => $pairKey, 'status' => 'active', 'assigned_by' => auth()->id(), 'assigned_at' => now()]);
                app(AuditService::class)->record('athlete_entry.created', $entry, null, $entry->only($entry->getFillable()));
            }
        });

        return redirect()->route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id])->with('success', $students->count().' participant(s) registered for '.$sport->name.'.');
    }

    public function removeParticipants(Request $request, Sport $sport): RedirectResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->firstOrFail();
        $data = $request->validate([
            'athlete_entry_ids' => ['required', 'array', 'min:1'],
            'athlete_entry_ids.*' => ['integer', 'distinct', 'exists:athlete_entries,id'],
        ]);
        $entries = AthleteEntry::query()
            ->where('edition_sport_id', $editionSport->id)
            ->where('status', 'active')
            ->whereIn('id', $data['athlete_entry_ids'])
            ->get();
        abort_unless($entries->count() === count($data['athlete_entry_ids']), 422, 'One or more selected registrations do not belong to this sport.');

        if ($editionSport->participant_type === 'dual') {
            $pairKeys = $entries->pluck('pair_key')->filter()->unique();
            $entries = AthleteEntry::query()
                ->where('edition_sport_id', $editionSport->id)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->whereIn('id', $entries->pluck('id'))
                    ->orWhereIn('pair_key', $pairKeys))
                ->get();
        }

        abort_if(
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'completed')->exists(),
            422,
            'Participant registrations cannot be changed after a bracket result has been recorded.'
        );

        DB::transaction(function () use ($entries, $editionSport): void {
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->delete();
            BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->delete();

            foreach ($entries as $entry) {
                $before = $entry->only($entry->getFillable());
                $entry->delete();
                app(AuditService::class)->record('athlete_entry.removed', $entry, $before, null);
            }
        });

        return redirect()->route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id])
            ->with('success', $entries->count().' participant registration(s) removed.');
    }
    public function events(Sport $sport): View { return view('admin.sports.events', ['sport' => $sport, 'events' => $sport->events()->with('edition')->latest('starts_at')->paginate(15)]); }
    private function currentEdition(): ?IntramuralEdition { return IntramuralEdition::query()->whereIn('status', ['draft', 'active'])->orderByDesc('starts_on')->first(); }
    private function selectedEdition(Request $request): ?IntramuralEdition
    {
        return $request->filled('edition_id')
            ? IntramuralEdition::query()->find($request->integer('edition_id'))
            : $this->currentEdition();
    }

    /** @return array{sport: Sport, edition: IntramuralEdition, editionSport: EditionSport, teams: \Illuminate\Support\Collection<int, array<string, mixed>>} */
    private function basketballScoreSheetContext(Request $request, Sport $sport, bool $loadRosters = true): array
    {
        abort_unless(str_contains(strtolower($sport->name), 'basketball'), 404);

        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);

        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->when($loadRosters, fn ($query) => $query->with([
                'athleteEntries' => fn ($entries) => $entries
                    ->where('status', 'active')
                    ->whereNotNull('team_id')
                    ->whereNotNull('student_id')
                    ->with(['student.course', 'team']),
            ]))
            ->firstOrFail();

        if (! $loadRosters) {
            return compact('sport', 'edition', 'editionSport') + ['teams' => collect()];
        }

        $teams = $this->scoreSheetTeams($editionSport);

        return compact('sport', 'edition', 'editionSport', 'teams');
    }

    /** @return array{sport: Sport, edition: IntramuralEdition, editionSport: EditionSport, teams: \Illuminate\Support\Collection<int, array<string, mixed>>} */
    private function volleyballScoreSheetContext(Request $request, Sport $sport, bool $loadRosters = true): array
    {
        abort_unless(str_contains(strtolower($sport->name), 'volleyball'), 404);

        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);

        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->when($loadRosters, fn ($query) => $query->with([
                'athleteEntries' => fn ($entries) => $entries
                    ->where('status', 'active')
                    ->whereNotNull('team_id')
                    ->whereNotNull('student_id')
                    ->with(['student.course', 'team']),
            ]))
            ->firstOrFail();

        return compact('sport', 'edition', 'editionSport') + [
            'teams' => $loadRosters ? $this->scoreSheetTeams($editionSport, true) : collect(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function scoreSheetTeams(EditionSport $editionSport, bool $includeSheetName = false): \Illuminate\Support\Collection
    {
        return $editionSport->athleteEntries
            ->where('status', 'active')
            ->filter(fn (AthleteEntry $entry) => $entry->team !== null && $entry->student !== null)
            ->groupBy('team_id')
            ->sortBy(fn ($entries) => $entries->first()->team->name)
            ->map(fn ($entries) => [
                'id' => $entries->first()->team->id,
                'name' => $entries->first()->team->name,
                'players' => $entries->map(function (AthleteEntry $entry) use ($includeSheetName) {
                    $player = [
                        'name' => $entry->student->full_name,
                        'course' => $entry->student->course?->code
                            ?: $entry->student->course?->name
                            ?: '',
                    ];
                    if ($includeSheetName) {
                        $player['sheet_name'] = $entry->student->last_name.'. '.Str::upper(Str::substr($entry->student->first_name, 0, 1)).'.';
                    }

                    return $player;
                })->values(),
            ])
            ->values();
    }
}
