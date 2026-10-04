<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Course;
use App\Models\IntramuralEdition;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(private readonly StudentService $studentService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->value() ?: 'active';
        $search = $request->string('search')->value();
        $courseId = $request->integer('course_id') ?: null;
        $activeEditionId = IntramuralEdition::query()->where('status', 'active')->value('id');

        $students = Student::query()
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($studentQuery) use ($search): void {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->with([
                'course',
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
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', ['students' => $students, 'status' => $status, 'search' => $search, 'courseId' => $courseId, 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.students.create', ['student' => new Student(), 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = $this->studentService->create($request->validated());

        return redirect()->route('admin.students.index')->with('success', "{$student->full_name} was added.");
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', ['student' => $student, 'courses' => Course::query()->where('status', 'active')->orderBy('name')->get()]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student = $this->studentService->update($student, $request->validated());

        return redirect()->route('admin.students.index')->with('success', "{$student->full_name} was updated.");
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
}
