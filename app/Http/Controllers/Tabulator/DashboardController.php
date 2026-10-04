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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BracketService $bracketService,
        private readonly StandingsService $standingsService,
        private readonly EligibilityService $eligibilityService,
    ) {
    }

    public function index(Request $request): View
    {
        $edition = $this->standingsService->activeEdition();
        $standings = $this->standingsService->standingsFor($edition);
        $editionSportIds = $edition?->editionSports()->pluck('id') ?? collect();
        $editionSports = $edition?->editionSports()
            ->with(['sport', 'sportResult'])
            ->get()
            ->filter(fn (EditionSport $editionSport): bool => $this->standingsService->pointRulesFor($editionSport) !== [])
            ->sortBy(fn (EditionSport $editionSport): string => $editionSport->sport?->name ?? '')
            ->values() ?? collect();
        $selectedSport = $editionSports->firstWhere('id', $request->integer('edition_sport_id')) ?? $editionSports->first();
        $teams = $edition?->teams()
            ->with('course')
            ->where('status', 'active')
            ->orderBy('name')
            ->get() ?? collect();

        return view('tabulator.dashboard', [
            'edition' => $edition,
            'standings' => $standings,
            'editionSports' => $editionSports,
            'selectedSport' => $selectedSport,
            'pointRules' => $selectedSport ? $this->standingsService->pointRulesFor($selectedSport) : [],
            'teams' => $teams,
            'teamsById' => $teams->keyBy('id'),
            'flaggedStudents' => $edition ? $this->eligibilityService->flaggedStudents($edition)->take(8) : collect(),
            'matches' => $editionSportIds->isEmpty()
                ? collect()
                : BracketMatch::query()
                    ->with(['editionSport.sport', 'competitorOne.team', 'competitorTwo.team', 'schedule'])
                    ->whereIn('edition_sport_id', $editionSportIds)
                    ->where('status', 'pending')
                    ->whereNotNull('competitor_one_id')
                    ->whereNotNull('competitor_two_id')
                    ->orderBy('bracket')
                    ->orderBy('round_number')
                    ->orderBy('match_number')
                    ->get(),
        ]);
    }

    public function declareSportWinners(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'edition_sport_id' => ['required', 'integer', 'exists:edition_sports,id'],
            'placements' => ['required', 'array'],
            'placements.*' => ['nullable', 'integer', 'exists:teams,id'],
            'tabulator_password' => ['required', 'string'],
        ]);
        $this->ensureCurrentPassword($request, $data['tabulator_password']);

        $editionSport = EditionSport::query()->with(['edition', 'sport'])->findOrFail($data['edition_sport_id']);
        abort_unless($editionSport->edition?->status === 'active', 404);

        $selectedTeamIds = collect($data['placements'] ?? [])
            ->filter(fn ($teamId): bool => filled($teamId))
            ->map(fn ($teamId): int => (int) $teamId)
            ->values();

        if ($selectedTeamIds->isEmpty()) {
            throw ValidationException::withMessages([
                'placements' => 'Choose at least one team placement.',
            ]);
        }

        if ($selectedTeamIds->unique()->count() !== $selectedTeamIds->count()) {
            throw ValidationException::withMessages([
                'placements' => 'A team can only appear once in the same sport result.',
            ]);
        }

        $validTeamCount = Team::query()
            ->where('edition_id', $editionSport->edition_id)
            ->where('status', 'active')
            ->whereIn('id', $selectedTeamIds)
            ->count();

        if ($validTeamCount !== $selectedTeamIds->count()) {
            throw ValidationException::withMessages([
                'placements' => 'Choose teams from the active intramurals edition.',
            ]);
        }

        $this->standingsService->recordSportResult($editionSport, $data['placements'], $request->user());

        return redirect()
            ->route('tabulator.dashboard', ['edition_sport_id' => $editionSport->id])
            ->with('success', $editionSport->sport?->name.' winners were declared.');
    }

    public function recordBracketResult(Request $request, BracketMatch $match): RedirectResponse
    {
        $match->loadMissing(['editionSport.edition', 'editionSport.sport', 'competitorOne', 'competitorTwo']);
        abort_unless($match->editionSport?->edition?->status === 'active', 404);

        $data = $request->validate([
            'winner_competitor_id' => ['required', 'integer', 'exists:bracket_competitors,id'],
            'tabulator_password' => ['required', 'string'],
        ]);
        $this->ensureCurrentPassword($request, $data['tabulator_password']);

        abort_unless(
            in_array((int) $data['winner_competitor_id'], [(int) $match->competitor_one_id, (int) $match->competitor_two_id], true),
            422,
            'Choose a competitor in this match.',
        );

        $winner = BracketCompetitor::query()->findOrFail($data['winner_competitor_id']);
        $this->bracketService->record($match, $winner);

        return back()->with('success', $winner->label.' was declared the winner.');
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
