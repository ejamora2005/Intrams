<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Team
    {
        return DB::transaction(function () use ($attributes): Team {
            $attributes['name'] = mb_strtoupper(trim($attributes['name']));
            if (! empty($attributes['course_id'])) {
                abort_if(Team::where('edition_id', $attributes['edition_id'])->where('course_id', $attributes['course_id'])->exists(), 422, 'Only one course-based team is allowed per edition.');
            }
            $team = Team::query()->create($attributes);
            $this->syncCourseStudents($team);
            $this->auditService->record('team.created', $team, null, $team->only($team->getFillable()));

            return $team;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Team $team, array $attributes): Team
    {
        return DB::transaction(function () use ($team, $attributes): Team {
            if ((int) $attributes['edition_id'] !== (int) $team->edition_id && $team->members()->exists()) {
                abort(422, 'A team edition cannot be changed while the team has roster members.');
            }

            if (! empty($attributes['course_id'])) {
                abort_if(Team::where('edition_id', $attributes['edition_id'])->where('course_id', $attributes['course_id'])->whereKeyNot($team->id)->exists(), 422, 'Only one course-based team is allowed per edition.');
            }

            $attributes['name'] = mb_strtoupper(trim($attributes['name']));
            $before = $team->only($team->getFillable());
            $team->update($attributes);
            $this->syncCourseStudents($team);
            $this->auditService->record('team.updated', $team, $before, $team->only($team->getFillable()));

            return $team;
        });
    }

    public function archive(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $before = $team->only($team->getFillable());
            $team->delete();
            $this->auditService->record('team.archived', $team, $before);
        });
    }

    public function restore(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $team->restore();
            $this->auditService->record('team.restored', $team, null, $team->only($team->getFillable()));
        });
    }

    public function assignStudent(Team $team, Student $student): TeamMember
    {
        abort_unless($team->status === 'active', 422, 'Only active teams can receive athletes.');
        abort_unless($student->status === 'active', 422, 'Only active students can be assigned to a team.');

        return DB::transaction(function () use ($team, $student): TeamMember {
            $existing = TeamMember::query()
                ->where('edition_id', $team->edition_id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->team_id !== $team->id) {
                abort(422, 'This student is already assigned to another team in this intramurals edition.');
            }

            if ($existing !== null) {
                return $existing;
            }

            $member = TeamMember::query()->create([
                'edition_id' => $team->edition_id,
                'team_id' => $team->id,
                'student_id' => $student->id,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);
            $this->auditService->record('team.member.assigned', $member, null, $member->only($member->getFillable()));

            return $member;
        });
    }

    public function removeStudent(TeamMember $member): void
    {
        DB::transaction(function () use ($member): void {
            $before = $member->only($member->getFillable());
            $member->delete();
            $this->auditService->record('team.member.removed', $member, $before);
        });
    }

    private function syncCourseStudents(Team $team): void
    {
        if (! $team->course_id || $team->status !== 'active') {
            return;
        }

        Student::query()
            ->where('status', 'active')
            ->where('course_id', $team->course_id)
            ->each(fn (Student $student) => $this->assignStudent($team, $student));
    }
}
