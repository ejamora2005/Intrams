<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class StudentService
{
    public function __construct(private readonly AuditService $auditService, private readonly TeamService $teamService)
    {
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Student
    {
        return DB::transaction(function () use ($attributes): Student {
            $student = Student::query()->create($attributes);
            $this->assignToCourseTeams($student);
            $this->auditService->record('student.created', $student, null, $student->only($student->getFillable()));

            return $student;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Student $student, array $attributes): Student
    {
        return DB::transaction(function () use ($student, $attributes): Student {
            $before = $student->only($student->getFillable());
            $student->update($attributes);
            $this->assignToCourseTeams($student);
            $this->auditService->record('student.updated', $student, $before, $student->only($student->getFillable()));

            return $student;
        });
    }

    public function archive(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $before = $student->only($student->getFillable());
            $student->delete();
            $this->auditService->record('student.archived', $student, $before, null);
        });
    }

    public function restore(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $student->restore();
            $this->auditService->record('student.restored', $student, null, $student->only($student->getFillable()));
        });
    }

    private function assignToCourseTeams(Student $student): void
    {
        if ($student->status !== 'active' || ! $student->course_id) {
            return;
        }

        Team::query()->where('course_id', $student->course_id)->where('status', 'active')->each(
            fn (Team $team) => $this->teamService->assignStudent($team, $student)
        );
    }
}
