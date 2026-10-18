<?php

namespace App\Http\Controllers\Tabulator;

use App\Http\Controllers\Controller;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\EditionSport;
use App\Models\Team;
use App\Services\BracketService;
use App\Services\EligibilityService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BracketService $bracketService,
        private readonly StandingsService $standingsService,
        private readonly EligibilityService $eligibilityService,
    ) {}

    public function index(Request $request): View
    {
        $edition = $this->standingsService->activeEdition();
        $standings = $this->standingsService->standingsFor($edition);
        $editionSportIds = $edition?->editionSports()->pluck('id') ?? collect();
        $editionSports = $edition?->editionSports()
            ->with(['sport', 'sportResult.submitter'])
            ->get()
            ->filter(fn (EditionSport $editionSport): bool => $editionSport->sport !== null && $this->standingsService->pointRulesFor($editionSport) !== [])
            ->sortBy(fn (EditionSport $editionSport): string => $editionSport->sport?->name ?? '')
            ->values() ?? collect();
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

        return view('tabulator.dashboard', [
            'edition' => $edition,
            'standings' => $standings,
            'editionSports' => $editionSports,
            'selectedSport' => $selectedSport,
            'pointRules' => $selectedSport ? $this->standingsService->pointRulesFor($selectedSport) : [],
            'teams' => $teams,
            'teamsById' => $teams->keyBy('id'),
            'eventPointRules' => $editionSports->mapWithKeys(fn ($sport) => [$sport->id => $this->standingsService->pointRulesFor($sport)]),
            'declaredCount' => $declaredCount,
            'pendingCount' => $editionSports->count() - $declaredCount,
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
                'tabulator_password' => ['required', 'string'],
            ]);
            $this->ensureCurrentPassword($request, $data['tabulator_password']);

            $editionSport = EditionSport::query()->with(['edition', 'sport'])->findOrFail($data['edition_sport_id']);
            abort_unless($editionSport->edition?->status === 'active', 404);
            $rules = $this->standingsService->pointRulesFor($editionSport);
            if ($rules === [] || array_diff(array_map('strval', array_keys($data['placements'])), array_map(fn ($rule) => (string) $rule['placement'], $rules)) !== []) {
                throw ValidationException::withMessages(['placements' => 'Choose only the placements configured for this event.']);
            }
            $allowRepeatedWinners = count($rules) > 3;

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

            $this->standingsService->recordSportResult($editionSport, $data['placements'], $request->user());

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
}
