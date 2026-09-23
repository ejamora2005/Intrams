<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\ParticipationRule;
use App\Models\Student;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParticipationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function createRule(array $data): ParticipationRule
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_active'])) {
                ParticipationRule::where('edition_id', $data['edition_id'])->where('is_active', true)->lockForUpdate()->update(['is_active' => false]);
            }

            $rule = ParticipationRule::create([...$data, 'is_active' => (bool) ($data['is_active'] ?? false)]);
            $this->audit->record('participation_rule.created', $rule, null, $rule->only($rule->getFillable()));

            return $rule;
        });
    }

    public function register(Event $event, Student $student, ?Team $team): EventRegistration
    {
        return $this->registerMany($event, collect([$student]), $team)->first();
    }

    /** @param Collection<int, Student> $students */
    public function registerMany(Event $event, Collection $students, ?Team $team): Collection
    {
        return DB::transaction(function () use ($event, $students, $team) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($event->status === 'scheduled', 422, 'Only scheduled events accept athletes.');
            abort_if($students->isEmpty(), 422, 'Select at least one athlete.');

            $requiresTeam = in_array($event->competition_type, ['team', 'dual'], true);
            if ($requiresTeam) {
                abort_unless($team instanceof Team && $team->status === 'active' && $team->edition_id === $event->edition_id, 422, 'Choose an active team from this edition.');
                if ($event->competition_type === 'dual') {
                    abort_unless($students->count() === 2, 422, 'Dual events require exactly two athletes from the selected team.');
                }

                $rosterIds = TeamMember::where('team_id', $team->id)->pluck('student_id');
                abort_unless($students->every(fn (Student $student) => $rosterIds->contains($student->id)), 422, 'Every selected athlete must belong to the selected team roster.');
            } else {
                abort_if($team !== null, 422, 'Individual events cannot use a team.');
                abort_unless($students->count() === 1, 422, 'Individual events allow one athlete at a time.');
            }

            abort_unless($students->every(fn (Student $student) => $student->status === 'active'), 422, 'Only active students can be added.');
            if ($event->competition_type === 'individual' && $event->capacity !== null) {
                $activeCount = EventRegistration::where('event_id', $event->id)->where('status', 'active')->lockForUpdate()->count();
                abort_if($activeCount + $students->count() > $event->capacity, 422, 'This event has reached its individual participant limit.');
            }

            $exists = EventRegistration::where('event_id', $event->id)->whereIn('student_id', $students->pluck('id'))->lockForUpdate()->exists();
            abort_if($exists, 422, 'One or more selected athletes are already in this event.');

            return $students->map(function (Student $student) use ($event, $team) {
                $this->validateLimits($event, $student);
                $registration = EventRegistration::create([
                    'event_id' => $event->id,
                    'student_id' => $student->id,
                    'team_id' => $team?->id,
                    'status' => 'active',
                    'registered_by' => auth()->id(),
                    'registered_at' => now(),
                ]);
                $this->audit->record('registration.created', $registration, null, $registration->only($registration->getFillable()));

                return $registration;
            });
        });
    }

    public function withdraw(EventRegistration $registration): void
    {
        DB::transaction(function () use ($registration) {
            $before = $registration->only($registration->getFillable());
            $registration->update(['status' => 'withdrawn']);
            $this->audit->record('registration.withdrawn', $registration, $before, $registration->only($registration->getFillable()));
        });
    }

    private function validateLimits(Event $event, Student $student): void
    {
        $rule = ParticipationRule::where('edition_id', $event->edition_id)->where('is_active', true)->lockForUpdate()->first();
        if (! $rule) {
            return;
        }

        $base = EventRegistration::query()
            ->join('events', 'event_registrations.event_id', '=', 'events.id')
            ->where('event_registrations.student_id', $student->id)
            ->where('event_registrations.status', 'active')
            ->where('events.edition_id', $event->edition_id);

        abort_if((clone $base)->count() >= $rule->max_events, 422, 'This athlete exceeds the edition event limit.');
        abort_if((clone $base)->distinct('events.sport_id')->count('events.sport_id') >= $rule->max_sports && ! (clone $base)->where('events.sport_id', $event->sport_id)->exists(), 422, 'This athlete exceeds the edition sport limit.');
    }
}
