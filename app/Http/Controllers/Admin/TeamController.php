<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTeamMemberRequest;
use App\Http\Requests\RemoveTeamMembersRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teamService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->value() ?: 'active';
        $search = $request->string('search')->value();
        $editionId = $request->integer('edition_id') ?: null;

        $teams = Team::query()
            ->with('edition')
            ->withCount('members')
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when(in_array($status, ['active', 'inactive', 'disqualified'], true), fn ($query) => $query->where('status', $status))
            ->when($editionId, fn ($query) => $query->where('edition_id', $editionId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(fn ($teamQuery) => $teamQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $editions = IntramuralEdition::query()->orderByDesc('starts_on')->orderByDesc('id')->get();

        return view('admin.teams.index', compact('teams', 'editions', 'status', 'search', 'editionId'));
    }

    public function create(Request $request): View
    {
        return view('admin.teams.create', [
            'team' => new Team(['edition_id' => $request->integer('edition_id') ?: null]),
            'editions' => IntramuralEdition::query()->orderByDesc('starts_on')->orderByDesc('id')->get(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = $this->teamService->create($request->validated());

        return redirect()->route('admin.teams.index')->with('success', "{$team->name} was created. Eligible course students were added to its roster.");
    }

    public function edit(Team $team): View
    {
        $team->load(['edition', 'members']);
        $courseId = request()->integer('course_id') ?: null;
        $rosterCourseId = request()->integer('roster_course_id') ?: null;
        $rosterSearch = request()->string('roster_search')->value();
        $assignedStudentIds = TeamMember::query()->where('edition_id', $team->edition_id)->pluck('student_id');
        $availableStudents = Student::query()
            ->where('status', 'active')
            ->whereNotIn('id', $assignedStudentIds)
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(250)
            ->get();
        $rosterMembers = TeamMember::query()
            ->with(['student.course'])
            ->where('team_id', $team->id)
            ->when($rosterCourseId, fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('course_id', $rosterCourseId)))
            ->when($rosterSearch !== '', function ($query) use ($rosterSearch): void {
                $query->whereHas('student', function ($studentQuery) use ($rosterSearch): void {
                    $studentQuery->where('student_number', 'like', "%{$rosterSearch}%")
                        ->orWhere('first_name', 'like', "%{$rosterSearch}%")
                        ->orWhere('middle_name', 'like', "%{$rosterSearch}%")
                        ->orWhere('last_name', 'like', "%{$rosterSearch}%");
                });
            })
            ->get()
            ->sortBy(fn ($member) => $member->student->full_name);

        return view('admin.teams.edit', [
            'team' => $team,
            'editions' => IntramuralEdition::query()->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'availableStudents' => $availableStudents,
            'courses' => \App\Models\Course::query()->where('status', 'active')->orderBy('name')->get(),
            'courseId' => $courseId,
            'rosterMembers' => $rosterMembers,
            'rosterCourseId' => $rosterCourseId,
            'rosterSearch' => $rosterSearch,
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teamService->update($team, $request->validated());

        return redirect()->route('admin.teams.index')->with('success', "{$team->name} was updated.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        $name = $team->name;
        $this->teamService->archive($team);

        return redirect()->route('admin.teams.index')->with('success', "{$name} was archived. Its roster history was preserved.");
    }

    public function restore(int $team): RedirectResponse
    {
        $archivedTeam = Team::withTrashed()->findOrFail($team);
        abort_unless($archivedTeam->trashed(), 404);
        $this->teamService->restore($archivedTeam);

        return redirect()->route('admin.teams.index', ['status' => 'archived'])->with('success', "{$archivedTeam->name} was restored.");
    }

    public function assignMember(AssignTeamMemberRequest $request, Team $team): RedirectResponse
    {
        abort_if($team->trashed(), 404);
        $students = Student::query()->whereIn('id', $request->validated('student_ids'))->get();
        foreach ($students as $student) { $this->teamService->assignStudent($team, $student); }

        return redirect()->route('admin.teams.index')->with('success', "{$students->count()} athlete(s) were assigned to {$team->name}.");
    }

    public function removeMember(Team $team, TeamMember $member): RedirectResponse
    {
        abort_unless($member->team_id === $team->id, 404);
        $studentName = $member->student?->full_name ?? 'The athlete';
        $this->teamService->removeStudent($member);

        return redirect()->route('admin.teams.index')->with('success', "{$studentName} was removed from {$team->name}.");
    }

    public function removeMembers(RemoveTeamMembersRequest $request, Team $team): RedirectResponse
    {
        abort_if($team->trashed(), 404);
        $memberIds = $request->validated('member_ids');
        $members = TeamMember::query()->where('team_id', $team->id)->whereIn('id', $memberIds)->get();
        abort_unless($members->count() === count($memberIds), 422, 'One or more selected athletes do not belong to this team.');

        foreach ($members as $member) {
            $this->teamService->removeStudent($member);
        }

        return redirect()->route('admin.teams.index')->with('success', $members->count().' athlete(s) were removed from '.$team->name.'.');
    }
}
