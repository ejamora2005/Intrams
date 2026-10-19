<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Course;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Services\AthleteEntryService;
use App\Services\StudentService;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly TeamService $teamService,
        private readonly AthleteEntryService $athleteEntryService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->value() ?: 'active';
        $search = trim($request->string('search')->value());
        $courseId = $request->integer('course_id') ?: null;
        $activeEditionId = IntramuralEdition::query()->where('status', 'active')->orderByDesc('starts_on')->orderByDesc('id')->value('id');
        $teamId = $request->integer('team_id') ?: null;

        $students = Student::query()
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search, $activeEditionId): void {
                $query->where(function ($studentQuery) use ($search, $activeEditionId): void {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%")
                        ->orWhereHas('course', fn ($courseQuery) => $courseQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('teamMembers', function ($memberQuery) use ($search, $activeEditionId): void {
                            $memberQuery
                                ->when($activeEditionId, fn ($editionQuery) => $editionQuery->where('edition_id', $activeEditionId))
                                ->whereHas('team', fn ($teamQuery) => $teamQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->when($teamId && $activeEditionId, fn ($query) => $query->whereHas('teamMembers', fn ($memberQuery) => $memberQuery->where('edition_id', $activeEditionId)->where('team_id', $teamId)))
            ->with([
                'course',
                'teamMembers' => fn ($query) => $query
                    ->when($activeEditionId, fn ($memberQuery) => $memberQuery->where('edition_id', $activeEditionId), fn ($memberQuery) => $memberQuery->whereRaw('1 = 0'))
                    ->with('team'),
                'athleteEntries' => function ($query) use ($activeEditionId): void {
                    $query
                        ->with('editionSport.sport')
                        ->where('status', 'active')
                        ->when($activeEditionId, fn ($entryQuery) => $entryQuery
                            ->whereHas('editionSport', fn ($editionSportQuery) => $editionSportQuery->where('edition_id', $activeEditionId)));
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.students.index', ['students' => $students, 'status' => $status, 'search' => $search, 'courseId' => $courseId, 'teamId' => $teamId, 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(), 'teams' => $activeEditionId ? Team::query()->where('edition_id', $activeEditionId)->where('status', 'active')->orderBy('name')->get() : collect()]);
    }

    public function create(): View
    {
        $edition = $this->activeEdition();

        return view('admin.students.create', [
            'student' => new Student,
            'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(),
            'edition' => $edition,
            'sportOptions' => $edition ? $this->athleteEntryService->selectableSportsFor($edition, true) : collect(),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $editionSportIds = $data['edition_sport_ids'] ?? [];
        $edition = $this->activeEdition();
        $sportOptions = $edition ? $this->athleteEntryService->selectableSportsFor($edition) : collect();

        if ($sportOptions->isNotEmpty() && $editionSportIds === []) {
            throw ValidationException::withMessages([
                'edition_sport_ids' => 'Select the sports this student joined.',
            ]);
        }

        if ($editionSportIds !== [] && ! $edition) {
            throw ValidationException::withMessages([
                'edition_sport_ids' => 'There is no active intramurals edition for these sport selections.',
            ]);
        }

        $student = DB::transaction(function () use ($data, $editionSportIds, $edition): Student {
            $student = $this->studentService->create(Arr::except($data, 'edition_sport_ids'));

            if ($edition && $editionSportIds !== []) {
                $team = $student->teamMembers()
                    ->with('team')
                    ->where('edition_id', $edition->id)
                    ->first()?->team;

                $this->athleteEntryService->registerSelectedSports($student, $edition, $editionSportIds, $team);
            }

            return $student;
        });

        return redirect()->route('admin.students.index')->with('success', "{$student->full_name} was added.");
    }

    public function edit(Student $student): View
    {
        $edition = IntramuralEdition::query()->where('status', 'active')->orderByDesc('starts_on')->orderByDesc('id')->first();
        $currentTeam = $edition ? $student->teamMembers()->with('team')->where('edition_id', $edition->id)->first()?->team : null;

        return view('admin.students.edit', [
            'student' => $student,
            'courses' => Course::query()->where('status', 'active')->orderBy('name')->get(),
            'edition' => $edition,
            'currentTeam' => $currentTeam,
            'teams' => $edition ? Team::query()->where('edition_id', $edition->id)->where('status', 'active')->orderBy('name')->get() : collect(),
            'sportOptions' => $edition ? $this->athleteEntryService->selectableSportsFor($edition, true) : collect(),
            'selectedSportIds' => $edition ? $student->athleteEntries()->where('status', 'active')->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id))->pluck('edition_sport_id')->map(fn ($id) => (string) $id)->values() : collect(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $editionSportIds = $data['edition_sport_ids'] ?? null;
        $edition = $this->activeEdition();
        $team = $edition ? $student->teamMembers()->with('team')->where('edition_id', $edition->id)->first()?->team : null;
        try {
            $student = DB::transaction(function () use ($student, $data, $edition, $editionSportIds, $team): Student {
                $student = $this->studentService->update($student, Arr::except($data, 'edition_sport_ids'));
                if ($edition && $editionSportIds !== null) {
                    $this->athleteEntryService->syncSelectedSports($student, $edition, $editionSportIds, $team);
                }

                return $student;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Preserve input on an unexpected save failure. Stale selections
            // receive their specific validation message from the entry service.
            report($exception);

            return back()->withInput()->withErrors([
                'edition_sport_ids' => 'The student update could not be saved because of a server error. No changes were saved. Ask an administrator to check the application logs.',
            ]);
        }

        return redirect()->route('admin.students.index')->with('success', "{$student->full_name} was updated.");
    }

    public function transferTeam(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['team_id' => ['required', 'integer', 'exists:teams,id']]);
        $edition = IntramuralEdition::query()->where('status', 'active')->orderByDesc('starts_on')->orderByDesc('id')->firstOrFail();
        $team = Team::query()->where('edition_id', $edition->id)->where('status', 'active')->findOrFail($data['team_id']);

        $this->teamService->transferStudent($team, $student);

        return redirect()->route('admin.students.edit', $student)->with('success', $student->full_name.' is now assigned to '.$team->name.'.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->full_name;
        $this->studentService->archive($student);

        return redirect()->route('admin.students.index')->with('success', "{$name} was archived.");
    }

    public function restore(int $student): RedirectResponse
    {
        $archivedStudent = Student::withTrashed()->findOrFail($student);
        abort_unless($archivedStudent->trashed(), 404);
        $this->studentService->restore($archivedStudent);

        return redirect()->route('admin.students.index', ['status' => 'archived'])->with('success', "{$archivedStudent->full_name} was restored.");
    }

    private function activeEdition(): ?IntramuralEdition
    {
        return IntramuralEdition::query()
            ->where('status', 'active')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();
    }
}
