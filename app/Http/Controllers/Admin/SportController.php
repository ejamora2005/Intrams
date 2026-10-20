<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportRequest;
use App\Http\Requests\UpdateSportRequest;
use App\Models\AthleteEntry;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\AuditService;
use App\Services\BracketService;
use App\Services\EligibilityService;
use App\Services\ScoreSheetImageOfficeService;
use App\Services\SportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SportController extends Controller
{
    public function __construct(
        private readonly SportService $service,
        private readonly BracketService $bracketService,
        private readonly EligibilityService $eligibilityService,
    ) {}

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
        $search = trim($request->string('search')->value());
        $category = $request->string('category')->value();
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
            ->when($search !== '', fn ($query) => $query->where(fn ($sportQuery) => $sportQuery
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')))
            ->when($category !== '' && array_key_exists($category, Sport::PARTICIPANT_CATEGORIES), fn ($query) => $query->where('participant_category', $category))
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderByRaw("FIELD(participant_category, 'male', 'female', 'mixed', 'open', 'custom')")
            ->orderBy('name')
            ->paginate(60)
            ->withQueryString();
        $editions = IntramuralEdition::query()->orderByDesc('starts_on')->orderByDesc('id')->get();

        return view('admin.sports.index', [
            'sports' => $sports,
            'status' => $status,
            'search' => $search,
            'category' => $category,
            'participantCategories' => Sport::PARTICIPANT_CATEGORIES,
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

        return view('admin.sports.create', ['sport' => new Sport, 'editionSport' => null, 'edition' => $edition]);
    }

    public function store(StoreSportRequest $request): RedirectResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 422, 'Create an edition before adding sports.');
        $sport = $this->service->create($request->validated());
        $gameMechanic = $request->input('game_mechanic', 'round_robin');
        if ($sport->isTrackAndField() || ($sport->participant_category === 'custom' && $gameMechanic === 'round_robin')) {
            $gameMechanic = 'custom';
        }
        EditionSport::query()->firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => $request->input('participant_type', 'team'), 'game_mechanic' => $gameMechanic, 'rules' => $request->input('rules'), 'status' => $request->input('configuration_status', 'preparation')]);

        return redirect()->route('admin.sports.index', ['edition_id' => $edition->id])->with('success', 'Sport created for '.$edition->name.'.');
    }

    public function edit(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        $editionSport = $edition ? EditionSport::firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => 'team', 'game_mechanic' => $sport->isTrackAndField() ? 'custom' : 'round_robin', 'status' => 'preparation']) : null;

        return view('admin.sports.edit', compact('sport', 'editionSport', 'edition'));
    }

    public function update(UpdateSportRequest $request, Sport $sport): RedirectResponse
    {
        $this->service->update($sport, $request->validated());
        $data = $request->validate(['participant_type' => ['required', 'in:team,dual,individual'], 'game_mechanic' => ['required', 'in:single_elimination,double_elimination,round_robin,custom'], 'rules' => ['nullable', 'string', 'max:5000'], 'configuration_status' => ['required', 'in:preparation,active,archived']]);
        $edition = $this->selectedEdition($request);
        if ($edition) {
            $editionSport = EditionSport::firstOrCreate(['edition_id' => $edition->id, 'sport_id' => $sport->id], ['participant_type' => 'team', 'game_mechanic' => 'round_robin', 'status' => 'preparation']);
            $gameMechanic = $data['game_mechanic'];
            if ($sport->isTrackAndField() || ($sport->participant_category === 'custom' && $gameMechanic === 'round_robin')) {
                $gameMechanic = 'custom';
            }
            $editionSport->update(['participant_type' => $data['participant_type'], 'game_mechanic' => $gameMechanic, 'rules' => $data['rules'], 'status' => $data['configuration_status']]);
        }

        return redirect()->route('admin.sports.index', ['edition_id' => $edition?->id])->with('success', 'Sport and mechanics updated.');
    }

    public function destroy(Sport $sport): RedirectResponse
    {
        $this->service->archive($sport);

        return redirect()->route('admin.sports.index')->with('success', 'Sport archived.');
    }

    public function restore(int $sport): RedirectResponse
    {
        $sport = Sport::withTrashed()->findOrFail($sport);
        $this->service->restore($sport);

        return redirect()->route('admin.sports.index', ['status' => 'archived'])->with('success', 'Sport restored.');
    }

    public function participants(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);

        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->with([
                'athleteEntries.student.course',
                'athleteEntries.student.teamMembers' => fn ($query) => $query->where('edition_id', $edition->id),
                'athleteEntries.team',
            ])
            ->firstOrFail();
        $activeEntries = $editionSport->athleteEntries
            ->where('status', 'active')
            ->map(function (AthleteEntry $entry): AthleteEntry {
                $entry->setAttribute('effective_medical_certificate_status', $this->eligibilityService->medicalCertificateStatusFor($entry));

                return $entry;
            })
            ->values();
        $activeTeams = Team::query()
            ->where('edition_id', $edition->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $entryTeamId = fn (AthleteEntry $entry): ?int => $this->resolvedEntryTeamId($entry, $edition->id);
        $entriesByTeamId = $activeEntries
            ->filter(fn (AthleteEntry $entry): bool => $entryTeamId($entry) !== null)
            ->groupBy(fn (AthleteEntry $entry): int => $entryTeamId($entry));
        $teamGroups = $activeTeams
            ->map(fn (Team $team): object => (object) [
                'team' => $team,
                'entries' => ($entriesByTeamId->get($team->id) ?? collect())
                    ->sortBy(fn (AthleteEntry $entry): string => $entry->student?->last_name.' '.$entry->student?->first_name)
                    ->values(),
            ])
            ->values();
        $registeredTeamGroups = $teamGroups->filter(fn (object $group): bool => $group->entries->isNotEmpty())->values();
        $participants = $activeEntries->groupBy(fn ($entry) => $entry->team?->name ?? 'Individual participants');
        $eligibilityByStudentId = $activeEntries
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => $this->eligibilityService->evaluateStudent($student, $edition),
            ]);

        $competitors = match ($editionSport->participant_type) {
            'team' => $registeredTeamGroups->map(fn (object $group): array => ['name' => $group->team->name, 'detail' => $group->entries->count().' registered athlete(s)'])->values(),
            'dual' => $activeEntries->groupBy(fn ($entry) => $entry->pair_key ?: 'entry-'.$entry->id)->map(fn ($entries) => ['name' => $entries->pluck('student.full_name')->implode(' / '), 'detail' => $entries->count().' paired athlete(s)'])->values(),
            default => $activeEntries->map(fn ($entry) => ['name' => $entry->student->full_name, 'detail' => $entry->student->student_number])->values(),
        };

        $bracketRounds = collect();
        if ($this->bracketService->usesBracketEngine($editionSport) && $competitors->isNotEmpty()) {
            $bracketSize = 2 ** (int) ceil(log(max($competitors->count(), 2), 2));
            $slots = $competitors->pluck('name')->pad($bracketSize, 'Bye')->all();
            $bracketRounds->push($slots);

            for ($round = 2; count($slots) > 1; $round++) {
                $slots = array_fill(0, (int) (count($slots) / 2), 'Winner of round '.($round - 1));
                $bracketRounds->push($slots);
            }
        }

        return view('admin.sports.participants', compact('sport', 'edition', 'editionSport', 'participants', 'teamGroups', 'competitors', 'bracketRounds', 'eligibilityByStudentId'));
    }

    private function resolvedEntryTeamId(AthleteEntry $entry, int $editionId): ?int
    {
        if ($entry->team_id) {
            return (int) $entry->team_id;
        }

        $teamMember = $entry->student?->teamMembers
            ->first(fn (TeamMember $member): bool => (int) $member->edition_id === $editionId);

        return $teamMember?->team_id ? (int) $teamMember->team_id : null;
    }

    public function bracket(Request $request, Sport $sport): View
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->firstOrFail();
        abort_if(! $this->bracketService->usesBracketEngine($editionSport), 422, 'Track and field events use manual results and do not have a tournament bracket.');
        $this->bracketService->initialize($editionSport);
        $matches = BracketMatch::query()
            ->where('edition_sport_id', $editionSport->id)
            ->with(['competitorOne.team', 'competitorTwo.team', 'winnerCompetitor.team', 'schedule'])
            ->orderBy('bracket')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get()
            ->groupBy('bracket');

        $bracketCompetitors = BracketCompetitor::query()
            ->where('edition_sport_id', $editionSport->id)
            ->with('team')
            ->orderBy('label')
            ->get();
        $bracketBlank = Schema::hasColumn('edition_sports', 'bracket_lineup_configured_at')
            && $editionSport->bracket_lineup_configured_at === null;
        $lineupIds = $matches->flatten(1)
            ->sortBy(fn (BracketMatch $match) => [$match->bracket === 'winners' ? 0 : 1, $match->round_number, $match->match_number])
            ->flatMap(fn (BracketMatch $match) => [$match->competitor_one_id, $match->competitor_two_id])
            ->filter()
            ->unique()
            ->values();
        $lineup = $lineupIds
            ->map(fn (int $id) => $bracketCompetitors->firstWhere('id', $id))
            ->filter()
            ->values();
        if ($lineup->isEmpty()) {
            $lineup = $bracketCompetitors->values();
        }
        $selectedCompetitors = $bracketBlank ? collect() : $lineup;
        $availableCompetitors = $bracketBlank
            ? $bracketCompetitors->values()
            : $bracketCompetitors->whereNotIn('id', $lineup->pluck('id'))->values();

        return view('admin.sports.bracket', compact('sport', 'edition', 'editionSport', 'matches', 'selectedCompetitors', 'availableCompetitors', 'bracketBlank'));
    }

    public function basketballScoreSheet(Request $request, Sport $sport): View|RedirectResponse
    {
        if (app(\App\Services\SportScoreSheetService::class)->profile($sport)['key'] === 'basketball-3x3') {
            return redirect()->route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $request->input('edition_id')]);
        }

        return view('admin.sports.basketball-score-sheet', $this->basketballScoreSheetContext($request, $sport));
    }

    public function volleyballScoreSheet(Request $request, Sport $sport): View|RedirectResponse
    {
        if (in_array(app(\App\Services\SportScoreSheetService::class)->profile($sport)['key'], ['beach-volleyball', 'school-volleyball'], true)) {
            return redirect()->route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $request->input('edition_id')]);
        }

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
            'winner_competitor_id' => ['nullable', 'required_without:winner_team_id', 'integer', 'exists:bracket_competitors,id'],
            'winner_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ], ['winner_competitor_id.required_without' => 'Choose the winning competitor.']);
        $winner = ! empty($data['winner_competitor_id'])
            ? BracketCompetitor::query()->findOrFail($data['winner_competitor_id'])
            : BracketCompetitor::query()
                ->where('edition_sport_id', $match->edition_sport_id)
                ->where('type', 'team')
                ->where('team_id', $data['winner_team_id'])
                ->first();
        if ($winner === null) {
            throw ValidationException::withMessages(['winner_team_id' => 'Choose a team in this match.']);
        }
        $this->bracketService->record($match, $winner);

        return redirect()->route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id])->with('success', $winner->label.' was declared the winner.');
    }

    public function scheduleBracketMatch(Request $request, Sport $sport, BracketMatch $match): RedirectResponse
    {
        abort_unless($match->editionSport->sport_id === $sport->id, 404);
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null && $match->editionSport->edition_id === $edition->id, 404);
        abort_unless($match->status === 'pending' && $match->competitor_one_id && $match->competitor_two_id, 422, 'Only a ready bracket match can be scheduled.');
        $match->loadMissing('editionSport.sport');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$edition->starts_on->format('Y-m-d'), 'before_or_equal:'.$edition->ends_on->format('Y-m-d')],
            // `period` remains accepted for older clients and saved links;
            // new forms send an exact 24-hour time instead.
            'time' => ['nullable', 'date_format:H:i', 'required_without:period'],
            'period' => ['nullable', 'in:morning,afternoon', 'required_without:time'],
            'venue' => ['nullable', 'string', 'max:120'],
        ]);
        $time = $data['time'] ?? ($data['period'] === 'morning' ? '08:00' : '13:00');
        $startsAt = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$time);
        $venue = trim((string) ($data['venue'] ?? '')) ?: $this->defaultScheduleVenue($match->editionSport->sport);
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
            ->with('success', 'Game '.$match->match_number.' scheduled for '.$startsAt->format('M j, Y g:i A').' at '.$venue.'.');
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

    public function reorderBracket(Request $request, Sport $sport): RedirectResponse|JsonResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()
            ->where('edition_id', $edition->id)
            ->where('sport_id', $sport->id)
            ->firstOrFail();
        $data = $request->validate([
            'competitor_ids' => ['required', 'array', 'min:2'],
            'competitor_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $this->bracketService->reorder($editionSport, $data['competitor_ids']);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id]),
            ]);
        }

        return redirect()
            ->route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $edition->id])
            ->with('success', 'Bracket lineup saved. The selected competitors were placed in the order you chose.');
    }

    private function defaultScheduleVenue(?Sport $sport): string
    {
        return str_starts_with(strtoupper((string) $sport?->code), 'BASKET-') ? 'MPCC' : 'TBA';
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
        $completePairKeys = $editionSport->participant_type === 'dual' ? $this->completePairKeys($editionSport) : collect();

        $studentQuery = Student::query()
            ->where('status', 'active')
            ->with('course')
            ->whereDoesntHave('athleteEntries', function ($entryQuery) use ($editionSport, $completePairKeys): void {
                $entryQuery->where('edition_sport_id', $editionSport->id);
                if ($editionSport->participant_type === 'dual') {
                    $entryQuery->where('status', 'active')->whereIn('pair_key', $completePairKeys);
                }
            })
            ->when(in_array($sport->participant_category, ['male', 'female'], true), fn ($query) => $query->where('gender', ucfirst($sport->participant_category)))
            ->when($requiresTeam && ! $teamId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->when($teamId, fn ($query) => $query->whereHas('teamMembers', fn ($memberQuery) => $memberQuery->where('team_id', $teamId)))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $students = $editionSport->participant_type === 'dual'
            ? $studentQuery->get()
            : $studentQuery->paginate(30)->withQueryString();

        $studentCollection = $students instanceof \Illuminate\Pagination\AbstractPaginator
            ? $students->getCollection()
            : $students;
        $eligibilityPreviewByStudentId = $this->eligibilityService->previewForStudents($studentCollection, $edition, $editionSport);

        return view('admin.sports.assign-participants', ['sport' => $sport, 'edition' => $edition, 'editionSport' => $editionSport, 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(), 'teams' => $teams, 'students' => $students, 'courseId' => $courseId, 'teamId' => $teamId, 'requiresTeam' => $requiresTeam, 'eligibilityPreviewByStudentId' => $eligibilityPreviewByStudentId]);
    }

    public function storeParticipants(Request $request, Sport $sport): RedirectResponse
    {
        $edition = $this->selectedEdition($request);
        abort_unless($edition !== null, 404);
        $editionSport = EditionSport::query()->where('edition_id', $edition->id)->where('sport_id', $sport->id)->firstOrFail();
        $data = $request->validate(['team_id' => ['nullable', 'exists:teams,id'], 'student_ids' => ['required', 'array', 'min:1'], 'student_ids.*' => ['integer', 'distinct', 'exists:students,id']]);
        $team = ! empty($data['team_id']) ? Team::findOrFail($data['team_id']) : null;
        $requiresTeam = in_array($editionSport->participant_type, ['team', 'dual'], true);
        abort_if($requiresTeam && (! $team || $team->edition_id !== $edition->id), 422, 'Choose a team from this edition.');
        abort_if(! $requiresTeam && $team, 422, 'Individual sports do not use a team assignment.');
        abort_if($editionSport->participant_type === 'dual' && count($data['student_ids']) !== 2, 422, 'Select exactly two students for this dual sport.');
        $students = Student::query()->where('status', 'active')->whereIn('id', $data['student_ids'])->get();
        abort_unless($students->count() === count($data['student_ids']), 422, 'Only active students can be assigned.');
        $this->eligibilityService->assertStudentsMatchSportCategory($students, $sport);

        if ($requiresTeam) {
            $roster = TeamMember::query()->where('team_id', $team->id)->pluck('student_id');
            abort_unless($students->every(fn ($student) => $roster->contains($student->id)), 422, 'Each selected student must be on the chosen team roster.');
        }

        abort_if(
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->where('status', 'completed')->exists(),
            422,
            'Participant registrations cannot be changed after a bracket result has been recorded.'
        );

        $medicalCertificateStatus = $this->eligibilityService->requiresMedicalCertificate($editionSport) ? 'pending' : 'not_required';

        DB::transaction(function () use ($students, $editionSport, $team, $medicalCertificateStatus): void {
            $existing = AthleteEntry::query()->where('edition_sport_id', $editionSport->id)
                ->whereIn('student_id', $students->pluck('id'))->lockForUpdate()->get()->keyBy('student_id');
            if ($editionSport->participant_type === 'dual') {
                $completePairKeys = $this->completePairKeys($editionSport);
                if ($existing->contains(fn (AthleteEntry $entry): bool => $entry->status === 'active' && $completePairKeys->contains($entry->pair_key))) {
                    throw ValidationException::withMessages(['student_ids' => 'One or more selected students already belong to a complete doubles pair. Refresh the list and choose unpaired players.']);
                }
            } else {
                abort_if($existing->isNotEmpty(), 422, 'One or more selected students are already registered.');
            }
            BracketMatch::query()->where('edition_sport_id', $editionSport->id)->delete();
            BracketCompetitor::query()->where('edition_sport_id', $editionSport->id)->delete();
            $pairKey = $editionSport->participant_type === 'dual' ? (string) Str::uuid() : null;
            foreach ($students as $student) {
                $entry = $existing->get($student->id) ?? new AthleteEntry;
                $before = $entry->exists ? $entry->only($entry->getFillable()) : null;
                $attributes = ['edition_sport_id' => $editionSport->id, 'student_id' => $student->id, 'team_id' => $team?->id, 'pair_key' => $pairKey, 'status' => 'active'];
                if (! $entry->exists || $entry->status !== 'active') {
                    $attributes += ['medical_certificate_status' => $medicalCertificateStatus, 'medical_certificate_reviewed_by' => null, 'medical_certificate_reviewed_at' => null, 'medical_certificate_notes' => null, 'assigned_by' => auth()->id(), 'assigned_at' => now()];
                }
                $entry->fill($attributes)->save();
                app(AuditService::class)->record($before === null ? 'athlete_entry.created' : 'athlete_entry.updated', $entry, $before, $entry->only($entry->getFillable()));
            }
        });

        return redirect()->route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id])->with([
            'success' => $students->count().' participant(s) registered for '.$sport->name.'.',
            'participation_warning' => $this->eligibilityService->registrationWarningForStudents($students, $edition),
        ]);
    }

    private function completePairKeys(EditionSport $editionSport): \Illuminate\Support\Collection
    {
        return AthleteEntry::query()->where('edition_sport_id', $editionSport->id)->where('status', 'active')
            ->whereNotNull('pair_key')->where('pair_key', '!=', '')
            ->groupBy('pair_key')->havingRaw('COUNT(*) >= 2')->pluck('pair_key');
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

    public function events(Sport $sport): View
    {
        return view('admin.sports.events', ['sport' => $sport, 'events' => $sport->events()->with('edition')->latest('starts_at')->paginate(15)]);
    }

    private function currentEdition(): ?IntramuralEdition
    {
        return IntramuralEdition::query()->whereIn('status', ['draft', 'active'])->orderByDesc('starts_on')->first();
    }

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
