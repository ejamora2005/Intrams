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
use App\Services\AthleteEntryService;
use App\Services\EligibilityService;
use App\Services\StandingsService;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StandingsService $standingsService,
        private readonly TeamService $teamService,
        private readonly EligibilityService $eligibilityService,
        private readonly AthleteEntryService $athleteEntryService,
    ) {}

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
        $flaggedStudents = $edition
            ? $this->eligibilityService->flaggedStudents($edition, $managedTeamId ? $teams->first() : null)
            : collect();
        $sportOptions = $edition && $selectedTeam
            ? $this->athleteEntryService->selectableSportsFor($edition)
            : collect();

        $selectedTeam?->load([
            'members' => fn ($query) => $query->with('student.course', 'student.athleteEntries.editionSport.sport')->latest('assigned_at'),
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
                ->with([
                    'student.course',
                    'student.teamMembers' => fn ($query) => $query->where('edition_id', $edition->id)->with('team'),
                    'team',
                    'editionSport.sport',
                ])
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

                    $query->whereHas('student.teamMembers', fn ($memberQuery) => $memberQuery
                        ->where('edition_id', $edition->id)
                        ->where('team_id', $managedTeamId))
                        ->orWhere(fn ($entryQuery) => $entryQuery
                            ->where('team_id', $managedTeamId)
                            ->whereDoesntHave('student.teamMembers', fn ($memberQuery) => $memberQuery->where('edition_id', $edition->id)));
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
            'sportOptions' => $sportOptions,
            'eligibilityByStudentId' => $eligibilityByStudentId,
            'flaggedStudents' => $flaggedStudents,
            'medicalCertificateEntries' => $medicalCertificateEntries,
            'medicalCertificateGroups' => $medicalCertificateEntries->groupBy('student_id'),
            'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(),
            'metrics' => [
                ['label' => 'Default teams', 'value' => $teams->count()],
                ['label' => 'Rostered players', 'value' => $teams->sum('members_count')],
                ['label' => 'Total points declared', 'value' => number_format($standings->sum(fn (array $row): float => (float) $row['tally']->points), 2)],
                ['label' => 'Scheduled games', 'value' => $editionSportIds->isEmpty() ? 0 : CompetitionSchedule::query()->whereIn('edition_sport_id', $editionSportIds)->count()],
                ['label' => 'Students pending med certs', 'value' => $medicalCertificateEntries->where('effective_medical_certificate_status', 'pending')->groupBy('student_id')->count()],
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
            'student_number' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'year_level' => ['nullable', 'string', 'max:30'],
            'section' => ['nullable', 'string', 'max:100'],
            'edition_sport_ids' => ['nullable', 'array', 'max:5'],
            'edition_sport_ids.*' => ['integer', 'distinct', 'exists:edition_sports,id'],
        ]);

        $editionSportIds = $data['edition_sport_ids'] ?? [];
        $sportOptions = $this->athleteEntryService->selectableSportsFor($team->edition);

        if ($sportOptions->isNotEmpty() && $editionSportIds === []) {
            throw ValidationException::withMessages([
                'edition_sport_ids' => 'Select the sports this player joined.',
            ]);
        }

        $student = DB::transaction(function () use ($data, $editionSportIds, $team): Student {
            $student = Student::query()->create([
                ...Arr::except($data, 'edition_sport_ids'),
                'school_year' => $team->edition?->school_year,
                'status' => 'active',
            ]);

            $this->teamService->assignStudent($team, $student);

            $this->athleteEntryService->registerSelectedSports($student, $team->edition, $editionSportIds, $team);

            return $student;
        });

        return redirect()
            ->route('gam.dashboard', ['team_id' => $team->id])
            ->with(['success' => $student->full_name.' was added to '.$team->name.'.', 'ops_view' => 'roster-management']);
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
            ->with(['success' => $student->full_name.' was assigned to '.$team->name.'.', 'ops_view' => 'roster-management']);
    }

    public function updatePlayerSports(Request $request, Team $team, Student $student): RedirectResponse
    {
        $this->ensureActiveTeam($team);
        abort_unless($team->members()->where('student_id', $student->id)->exists(), 404);

        $data = $request->validate([
            'edition_sport_ids' => ['nullable', 'array', 'max:5'],
            'edition_sport_ids.*' => ['integer', 'distinct', 'exists:edition_sports,id'],
        ]);

        DB::transaction(fn () => $this->athleteEntryService->syncSelectedSports($student, $team->edition, $data['edition_sport_ids'] ?? [], $team));

        return redirect()->route('gam.dashboard', ['team_id' => $team->id])
            ->with(['success' => $student->full_name.' sports were updated.', 'ops_view' => 'roster-management']);
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

        return back()->with(['success' => 'Medical certificate status updated for '.$entry->student?->full_name.'.', 'ops_view' => 'medical-certificates']);
    }

    public function updateStudentMedicalCertificates(Request $request, Student $student): RedirectResponse
    {
        $edition = $this->standingsService->activeEdition();
        abort_unless($edition !== null, 404);

        $data = $request->validate([
            'medical_certificate_status' => ['required', 'in:pending,verified,rejected'],
            'medical_certificate_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $entries = AthleteEntry::query()
            ->with(['editionSport.sport', 'student'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))
            ->get()
            ->filter(fn (AthleteEntry $entry): bool => $entry->editionSport !== null && $this->eligibilityService->requiresMedicalCertificate($entry->editionSport));

        abort_if($entries->isEmpty(), 404);
        abort_unless($entries->every(fn (AthleteEntry $entry): bool => $this->userCanManageEntry($request->user(), $entry)), 404);

        $reviewed = in_array($data['medical_certificate_status'], ['verified', 'rejected'], true);

        DB::transaction(function () use ($entries, $data, $reviewed, $request): void {
            foreach ($entries as $entry) {
                $entry->update([
                    'medical_certificate_status' => $data['medical_certificate_status'],
                    'medical_certificate_notes' => $data['medical_certificate_notes'] ?? null,
                    'medical_certificate_reviewed_by' => $reviewed ? $request->user()?->id : null,
                    'medical_certificate_reviewed_at' => $reviewed ? now() : null,
                ]);
            }
        });

        return redirect()->to(route('gam.dashboard').'#medical-certificates')
            ->with(['success' => 'Medical certificate status updated for '.$student->full_name.' across '.$entries->count().' events.', 'ops_view' => 'medical-certificates']);
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

        $membership = TeamMember::query()
            ->where('edition_id', $entry->editionSport?->edition_id)
            ->where('student_id', $entry->student_id)
            ->first();

        return $membership
            ? (int) $membership->team_id === $managedTeamId
            : (int) $entry->team_id === $managedTeamId;
    }
}
