<?php

namespace App\Http\Controllers\Tabulator;

use App\Http\Controllers\Controller;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\Team;
use App\Services\BracketService;
use App\Services\DeclaredResultsExportService;
use App\Services\EligibilityService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BracketService $bracketService,
        private readonly StandingsService $standingsService,
        private readonly EligibilityService $eligibilityService,
        private readonly DeclaredResultsExportService $declaredResultsExportService,
    ) {}

    public function exportResults(string $format): Response
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);

        $edition = $this->standingsService->activeEdition();
        $isPdf = $format === 'pdf';
        $content = $isPdf
            ? $this->declaredResultsExportService->pdf($edition)
            : $this->declaredResultsExportService->xlsx($edition);

        return response($content, 200, [
            'Content-Type' => $isPdf ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->declaredResultsExportService->filename($edition, $format).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function index(Request $request): View
    {
        $edition = $this->standingsService->activeEdition();
        $scoreGroup = $this->standingsService->normalizeScoreGroup($request->input('score_group', 'student'));
        $standings = $this->standingsService->standingsFor($edition, $scoreGroup);
        $publishedStandings = $this->standingsService->publishedStandingsFor($edition, $scoreGroup);
        $editionSports = $this->standingsService->scoringEventsFor($edition);
        $editionSportIds = $editionSports->pluck('id');
        $selectedSport = $editionSports->firstWhere('id', (int) $request->input('edition_sport_id', $request->old('edition_sport_id', 0)));
        $teams = $edition?->teams()
            ->with('course')
            ->where('status', 'active')
            ->orderBy('name')
            ->get() ?? collect();

        $matches = $editionSportIds->isEmpty() ? collect() : BracketMatch::query()
            ->with(['editionSport.sport', 'competitorOne.team', 'competitorTwo.team', 'schedule'])
            ->whereIn('edition_sport_id', $editionSportIds)
            ->when(Schema::hasColumn('edition_sports', 'bracket_lineup_configured_at'), fn ($query) => $query
                ->whereHas('editionSport', fn ($sportQuery) => $sportQuery->whereNotNull('bracket_lineup_configured_at')))
            ->where('status', 'pending')->whereNotNull('competitor_one_id')->whereNotNull('competitor_two_id')
            ->orderBy('edition_sport_id')->orderBy('round_number')->orderBy('match_number')->get();
        $declaredCount = $editionSports->filter(fn ($sport) => $sport->sportResult !== null)->count();
        $pendingScoreUpdateEvents = $editionSports
            ->filter(fn ($sport) => $this->standingsService->hasPendingScoreUpdate($sport))
            ->values();
        $pendingScoreUpdates = $pendingScoreUpdateEvents->count();

        return view('tabulator.dashboard', [
            'edition' => $edition,
            'standings' => $standings,
            'publishedStandings' => $publishedStandings,
            'scoreGroup' => $scoreGroup,
            'editionSports' => $editionSports,
            'selectedSport' => $selectedSport,
            'pointRules' => $selectedSport ? $this->standingsService->pointRulesFor($selectedSport) : [],
            'allowRepeatedWinners' => $selectedSport && $this->standingsService->allowsRepeatedWinnersFor($selectedSport),
            'teams' => $teams,
            'teamsById' => $teams->keyBy('id'),
            'eventPointRules' => $editionSports->mapWithKeys(fn ($sport) => [$sport->id => $this->standingsService->pointRulesFor($sport)]),
            'declaredCount' => $declaredCount,
            'pendingCount' => $editionSports->count() - $declaredCount,
            'pendingScoreUpdates' => $pendingScoreUpdates,
            'pendingScoreUpdateEvents' => $pendingScoreUpdateEvents,
            'recentResults' => $editionSports->filter(fn ($sport) => $sport->sportResult !== null)
                ->sortByDesc(fn ($sport) => $sport->sportResult->submitted_at?->timestamp ?? 0)->take(4),
            'nextPendingSport' => $editionSports->first(fn ($sport) => $sport->sportResult === null && $sport->id !== $selectedSport?->id),
            'flaggedStudents' => $edition ? $this->eligibilityService->flaggedStudents($edition) : collect(),
            'matches' => $matches,
        ]);
    }

    public function declareSportWinners(Request $request): RedirectResponse
    {
        try {
            $data = $request->validate([
                'edition_sport_id' => ['required', 'integer', 'exists:edition_sports,id'],
                'placements' => ['required', 'array'],
                'placements.*' => ['nullable', 'integer', 'exists:teams,id'],
                'score_group' => ['nullable', 'string', 'in:student,employee'],
                'tabulator_password' => ['required', 'string'],
            ]);
            $this->ensureCurrentPassword($request, $data['tabulator_password']);

            $editionSport = EditionSport::query()->with(['edition', 'sport'])->findOrFail($data['edition_sport_id']);
            abort_unless($editionSport->edition?->status === 'active', 404);
            $rules = $this->standingsService->pointRulesFor($editionSport);
            if ($rules === [] || array_diff(array_map('strval', array_keys($data['placements'])), array_map(fn ($rule) => (string) $rule['placement'], $rules)) !== []) {
                throw ValidationException::withMessages(['placements' => 'Choose only the placements configured for this event.']);
            }
            $this->ensureCurrentScoringEvent($editionSport);
            $allowRepeatedWinners = $this->standingsService->allowsRepeatedWinnersFor($editionSport);

            $selectedTeamIds = collect($data['placements'] ?? [])
                ->filter(fn ($teamId): bool => filled($teamId))
                ->map(fn ($teamId): int => (int) $teamId)
                ->values();

            if ($selectedTeamIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'placements' => 'Choose at least one team placement.',
                ]);
            }

            if (! $allowRepeatedWinners && $selectedTeamIds->unique()->count() !== $selectedTeamIds->count()) {
                throw ValidationException::withMessages([
                    'placements' => 'A faction can only appear once in a top-three sport result.',
                ]);
            }

            $uniqueTeamIds = $selectedTeamIds->unique()->values();
            $validTeamCount = Team::query()
                ->where('edition_id', $editionSport->edition_id)
                ->where('status', 'active')
                ->whereIn('id', $uniqueTeamIds)
                ->count();

            if ($validTeamCount !== $uniqueTeamIds->count()) {
                throw ValidationException::withMessages([
                    'placements' => 'Choose teams from the active intramurals edition.',
                ]);
            }

            $this->standingsService->recordSportResult($editionSport, $data['placements'], $request->user(), $data['score_group'] ?? 'student', false);

            return redirect()
                ->to(route('tabulator.dashboard', ['edition_sport_id' => $editionSport->id]).'#declare-sports')
                ->with(['success' => $editionSport->sport?->name.' winners were declared.', 'ops_view' => 'declare-sports']);
        } catch (ValidationException $exception) {
            $exception->redirectTo(route('tabulator.dashboard', [
                'edition_sport_id' => $request->integer('edition_sport_id'),
                'view' => 'declare-sports', 'modal' => 'tabulator-declare-sports-modal',
            ]).'#declare-sports');
            throw $exception;
        }
    }

    public function publishScores(Request $request): RedirectResponse
    {
        try {
            $data = $request->validate([
                'score_update_password' => ['required', 'string'],
            ]);
            if (! hash_equals((string) config('intramurals.score_update_password'), (string) $data['score_update_password'])) {
                throw ValidationException::withMessages([
                    'score_update_password' => 'The score update password did not match.',
                ]);
            }

            $edition = $this->standingsService->activeEdition();
            abort_unless($edition !== null, 404);
            $count = $this->standingsService->publishPendingScores($edition, $request->user());

            return redirect()->to(route('tabulator.dashboard', ['view' => 'declared-results']).'#declared-results')
                ->with(['success' => $count === 0 ? 'There are no pending score updates.' : $count.' score update(s) published to the landing page.', 'ops_view' => 'declared-results']);
        } catch (ValidationException $exception) {
            $exception->redirectTo(route('tabulator.dashboard', [
                'view' => 'declared-results', 'modal' => 'tabulator-score-update-modal',
            ]).'#declared-results');
            throw $exception;
        }
    }

    public function recordBracketResult(Request $request, BracketMatch $match): RedirectResponse
    {
        try {
            $match->loadMissing(['editionSport.edition', 'editionSport.sport', 'competitorOne', 'competitorTwo']);
            abort_unless($match->editionSport?->edition?->status === 'active', 404);

            $data = $request->validate([
                'winner_competitor_id' => ['required', 'integer', 'exists:bracket_competitors,id'],
                'tabulator_password' => ['required', 'string'],
            ]);
            $this->ensureCurrentPassword($request, $data['tabulator_password']);

            $this->ensureCurrentScoringEvent($match->editionSport);
            $winner = BracketCompetitor::query()->findOrFail($data['winner_competitor_id']);
            $this->bracketService->record($match, $winner);

            return redirect()->to(route('tabulator.dashboard').'#match-winners')
                ->with(['success' => $winner->label.' was declared the winner.', 'ops_view' => 'match-winners']);
        } catch (ValidationException $exception) {
            $exception->redirectTo(route('tabulator.dashboard', [
                'view' => 'match-winners', 'modal' => 'match-winner-modal-'.$match->id,
            ]).'#match-winners');
            throw $exception;
        }
    }

    private function ensureCurrentPassword(Request $request, string $password): void
    {
        if (! Hash::check($password, (string) $request->user()?->password)) {
            throw ValidationException::withMessages([
                'tabulator_password' => 'The tabulator password did not match.',
            ]);
        }
    }

    private function ensureCurrentScoringEvent(EditionSport $event): void
    {
        if (! $this->standingsService->scoringEventsFor($event->edition)->contains('id', $event->id)) {
            throw ValidationException::withMessages([
                'edition_sport_id' => 'This event is archived or has been replaced. Choose a current event from the scoring catalogue, using its Men or Women division when available.',
            ]);
        }
    }
}
