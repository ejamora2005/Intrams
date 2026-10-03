<?php

namespace App\Http\Controllers\Gam;

use App\Http\Controllers\Controller;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\Course;
use App\Models\Student;
use App\Models\Team;
use App\Services\StandingsService;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StandingsService $standingsService,
        private readonly TeamService $teamService,
    ) {
    }

    public function index(Request $request): View
    {
        $edition = $this->standingsService->activeEdition();
        $standings = $this->standingsService->standingsFor($edition);
        $editionSportIds = $edition?->editionSports()->pluck('id') ?? collect();
        $teams = $edition?->teams()
            ->with('course')
            ->withCount('members')
            ->where('status', 'active')
            ->orderBy('name')
            ->get() ?? collect();
        $selectedTeam = $teams->firstWhere('id', $request->integer('team_id')) ?? $teams->first();

        $selectedTeam?->load([
            'members' => fn ($query) => $query->with('student.course')->latest('assigned_at'),
        ]);

        $availableStudents = $edition && $selectedTeam
            ? Student::query()
                ->with('course')
                ->where('status', 'active')
                ->whereDoesntHave('teamMembers', fn ($query) => $query->where('edition_id', $edition->id))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(75)
                ->get()
            : collect();

        return view('gam.dashboard', [
            'edition' => $edition,
            'standings' => $standings,
            'teams' => $teams,
            'selectedTeam' => $selectedTeam,
            'availableStudents' => $availableStudents,
            'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(),
            'metrics' => [
                ['label' => 'Default teams', 'value' => $teams->count()],
                ['label' => 'Rostered players', 'value' => $teams->sum('members_count')],
                ['label' => 'Total points declared', 'value' => number_format($standings->sum(fn (array $row): float => (float) $row['tally']->points), 2)],
                ['label' => 'Scheduled games', 'value' => $editionSportIds->isEmpty() ? 0 : CompetitionSchedule::query()->whereIn('edition_sport_id', $editionSportIds)->count()],
            ],
            'upcomingMatches' => $editionSportIds->isEmpty()
                ? collect()
                : BracketMatch::query()
                    ->with(['editionSport.sport', 'competitorOne', 'competitorTwo', 'schedule'])
                    ->whereIn('edition_sport_id', $editionSportIds)
                    ->where('status', 'pending')
                    ->orderBy('bracket')
                    ->orderBy('round_number')
                    ->orderBy('match_number')
                    ->limit(8)
                    ->get(),
        ]);
    }

    public function storePlayer(Request $request, Team $team): RedirectResponse
    {
        $this->ensureActiveTeam($team);

        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:255', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'year_level' => ['nullable', 'string', 'max:30'],
            'section' => ['nullable', 'string', 'max:100'],
        ]);

        $student = DB::transaction(function () use ($data, $team): Student {
            $student = Student::query()->create([
                ...$data,
                'school_year' => $team->edition?->school_year,
                'status' => 'active',
            ]);

            $this->teamService->assignStudent($team, $student);

            return $student;
        });

        return redirect()
            ->route('gam.dashboard', ['team_id' => $team->id])
            ->with('success', $student->full_name.' was added to '.$team->name.'.');
    }

    public function assignExistingPlayer(Request $request, Team $team): RedirectResponse
    {
        $this->ensureActiveTeam($team);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $student = Student::query()->findOrFail($data['student_id']);
        $this->teamService->assignStudent($team, $student);

        return redirect()
            ->route('gam.dashboard', ['team_id' => $team->id])
            ->with('success', $student->full_name.' was assigned to '.$team->name.'.');
    }

    private function ensureActiveTeam(Team $team): void
    {
        $team->loadMissing('edition');

        abort_unless($team->status === 'active' && $team->edition?->status === 'active', 404);
    }
}
