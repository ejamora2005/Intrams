<?php

namespace App\Http\Controllers\Gam;

use App\Http\Controllers\Controller;
use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\Course;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\EligibilityService;
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
        private readonly EligibilityService $eligibilityService,
    ) {
    }

    public function index(Request $request): View
    {
        $edition = $this->standingsService->activeEdition();
        $editionSportIds = $edition?->editionSports()->pluck('id') ?? collect();
        $managedTeamId = $request->user()?->managed_team_id;
        $hasAllFactionAccess = $managedTeamId === null;
        $standings = $this->standingsService->standingsFor($edition)
            ->when($managedTeamId, fn ($rows) => $rows->filter(fn (array $row): bool => (int) $row['team']->id === (int) $managedTeamId)->values());
        $teams = $edition?->teams()
            ->with('course')
            ->withCount('members')
            ->where('status', 'active')
            ->when($managedTeamId, fn ($query) => $query->whereKey($managedTeamId))
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

        $eligibilityByStudentId = $edition && $selectedTeam
            ? $selectedTeam->members
                ->pluck('student')
                ->filter()
                ->mapWithKeys(fn (Student $student): array => [
                    $student->id => $this->eligibilityService->evaluateStudent($student, $edition),
                ])
            : collect();

        $medicalCertificateEntries = $editionSportIds->isEmpty()
            ? collect()
            : AthleteEntry::query()
                ->with(['student.course', 'team', 'editionSport.sport'])
                ->where('status', 'active')
                ->whereIn('edition_sport_id', $editionSportIds)
                ->where(function ($query) use ($edition, $managedTeamId, $hasAllFactionAccess): void {
                    if (! $edition) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    if ($hasAllFactionAccess) {
                        return;
                    }

                    $query
                        ->where('team_id', $managedTeamId)
                        ->orWhereHas('student.teamMembers', fn ($memberQuery) => $memberQuery
                            ->where('edition_id', $edition->id)
                            ->where('team_id', $managedTeamId));
                })
                ->latest('assigned_at')
                ->get()
                ->filter(fn (AthleteEntry $entry): bool => $entry->editionSport !== null && $this->eligibilityService->requiresMedicalCertificate($entry->editionSport))
                ->map(function (AthleteEntry $entry): AthleteEntry {
                    $entry->setAttribute('effective_medical_certificate_status', $this->eligibilityService->medicalCertificateStatusFor($entry));

                    return $entry;
                })
                ->values();

        return view('gam.dashboard', [
            'edition' => $edition,
            'standings' => $standings,
            'teams' => $teams,
            'selectedTeam' => $selectedTeam,
            'managedTeamId' => $managedTeamId,
            'hasAllFactionAccess' => $hasAllFactionAccess,
            'availableStudents' => $availableStudents,
            'eligibilityByStudentId' => $eligibilityByStudentId,
            'medicalCertificateEntries' => $medicalCertificateEntries,
            'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(),
            'metrics' => [
                ['label' => 'Default teams', 'value' => $teams->count()],
                ['label' => 'Rostered players', 'value' => $teams->sum('members_count')],
                ['label' => 'Total points declared', 'value' => number_format($standings->sum(fn (array $row): float => (float) $row['tally']->points), 2)],
                ['label' => 'Scheduled games', 'value' => $editionSportIds->isEmpty() ? 0 : CompetitionSchedule::query()->whereIn('edition_sport_id', $editionSportIds)->count()],
                ['label' => 'Pending med certs', 'value' => $medicalCertificateEntries->where('effective_medical_certificate_status', 'pending')->count()],
            ],
            'upcomingMatches' => $editionSportIds->isEmpty()
                ? collect()
                : BracketMatch::query()
                    ->with(['editionSport.sport', 'competitorOne.team', 'competitorTwo.team', 'schedule'])
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

    public function updateMedicalCertificate(Request $request, AthleteEntry $entry): RedirectResponse
    {
        $entry->loadMissing(['editionSport.edition', 'editionSport.sport', 'student']);

        abort_unless($entry->status === 'active' && $entry->editionSport?->edition?->status === 'active', 404);
        abort_unless($this->userCanManageEntry($request->user(), $entry), 404);
        abort_unless($this->eligibilityService->requiresMedicalCertificate($entry->editionSport), 422, 'This event does not require a medical certificate.');

        $data = $request->validate([
            'medical_certificate_status' => ['required', 'in:pending,verified,rejected'],
            'medical_certificate_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewed = in_array($data['medical_certificate_status'], ['verified', 'rejected'], true);

        $entry->update([
            'medical_certificate_status' => $data['medical_certificate_status'],
            'medical_certificate_notes' => $data['medical_certificate_notes'] ?? null,
            'medical_certificate_reviewed_by' => $reviewed ? $request->user()?->id : null,
            'medical_certificate_reviewed_at' => $reviewed ? now() : null,
        ]);

        return back()->with('success', 'Medical certificate status updated for '.$entry->student?->full_name.'.');
    }

    private function ensureActiveTeam(Team $team): void
    {
        $team->loadMissing('edition');

        abort_unless($team->status === 'active' && $team->edition?->status === 'active', 404);
        abort_unless($this->userCanManageTeam(request()->user(), $team), 404);
    }

    private function userCanManageTeam(?User $user, Team $team): bool
    {
        if ($user?->role !== 'gam') {
            return false;
        }

        return $user->managed_team_id === null || (int) $user->managed_team_id === (int) $team->id;
    }

    private function userCanManageEntry(?User $user, AthleteEntry $entry): bool
    {
        if ($user?->role !== 'gam') {
            return false;
        }

        if ($user->managed_team_id === null) {
            return true;
        }

        $managedTeamId = (int) $user->managed_team_id;

        if ((int) $entry->team_id === $managedTeamId) {
            return true;
        }

        return TeamMember::query()
            ->where('edition_id', $entry->editionSport?->edition_id)
            ->where('team_id', $managedTeamId)
            ->where('student_id', $entry->student_id)
            ->exists();
    }
}
