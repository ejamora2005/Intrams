<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(private readonly AuditService $auditService, private readonly BracketService $bracketService)
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

    public function transferStudent(Team $team, Student $student): void
    {
        $team->loadMissing('edition');
        abort_unless($team->status === 'active' && $team->edition?->status === 'active', 422, 'Choose an active faction in the current edition.');
        abort_unless($student->status === 'active', 422, 'Only active students can be transferred.');

        DB::transaction(function () use ($team, $student): void {
            $member = TeamMember::query()
                ->where('edition_id', $team->edition_id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($member?->team_id === $team->id) {
                return;
            }

            $entries = AthleteEntry::query()
                ->with('editionSport')
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $team->edition_id))
                ->get();
            $sportIds = $entries->pluck('edition_sport_id')->unique()->all();

            abort_if($entries->contains(fn (AthleteEntry $entry): bool => $entry->editionSport?->participant_type === 'dual'), 422, 'Remove this student from their dual pair before transferring factions.');
            abort_if(SportResult::query()->whereIn('edition_sport_id', $sportIds)->exists(), 422, 'A result has already been declared for one of this student\'s events.');
            abort_if(BracketMatch::query()->whereIn('edition_sport_id', $sportIds)->where('status', 'completed')->exists(), 422, 'A match result has already been recorded for one of this student\'s events.');
            abort_if(CompetitionSchedule::query()->whereIn('edition_sport_id', $sportIds)->exists(), 422, 'An event for this student is already scheduled.');

            if ($member) {
                $before = $member->only($member->getFillable());
                $member->update(['team_id' => $team->id, 'assigned_by' => auth()->id(), 'assigned_at' => now()]);
                $this->auditService->record('team.member.transferred', $member, $before, $member->only($member->getFillable()));
            } else {
                $this->assignStudent($team, $student);
            }

            foreach ($entries as $entry) {
                if (in_array($entry->editionSport?->participant_type, ['team', 'dual'], true)) {
                    $before = $entry->only($entry->getFillable());
                    $entry->update(['team_id' => $team->id]);
                    $this->auditService->record('athlete_entry.team_transferred', $entry, $before, $entry->only($entry->getFillable()));
                }
            }

            foreach ($entries->filter(fn (AthleteEntry $entry): bool => $entry->editionSport?->participant_type === 'team')->pluck('editionSport')->unique('id') as $editionSport) {
                if (in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin'], true)
                    && BracketMatch::query()->where('edition_sport_id', $editionSport->id)->exists()) {
                    $this->bracketService->reset($editionSport);
                }
            }
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
