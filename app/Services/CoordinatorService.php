<?php

namespace App\Services;

use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CoordinatorService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly CoordinatorDeviceService $deviceService,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $coordinator = User::query()->create([
                ...Arr::except($attributes, ['password']),
                'password' => Hash::make($attributes['password']),
                'role' => 'coordinator',
            ]);
            $this->auditService->record('coordinator.created', $coordinator, null, $this->safeValues($coordinator));

            return $coordinator;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $coordinator, array $attributes): User
    {
        return DB::transaction(function () use ($coordinator, $attributes): User {
            $before = $this->safeValues($coordinator);
            $changes = Arr::except($attributes, ['password']);

            if (! empty($attributes['password'])) {
                $changes['password'] = Hash::make($attributes['password']);
            }

            $coordinator->update($changes);
            $this->auditService->record('coordinator.updated', $coordinator, $before, $this->safeValues($coordinator));

            return $coordinator;
        });
    }

    public function assign(User $coordinator, Event $event): CoordinatorAssignment
    {
        return DB::transaction(function () use ($coordinator, $event): CoordinatorAssignment {
            $assignment = CoordinatorAssignment::query()->firstOrNew([
                'coordinator_id' => $coordinator->id,
                'event_id' => $event->id,
            ]);
            $before = $assignment->exists ? $assignment->only($assignment->getFillable()) : null;
            $assignment->fill([
                'assigned_by' => auth()->id(),
                'status' => 'active',
                'approved_at' => now(),
                'revoked_at' => null,
            ])->save();
            $this->auditService->record('coordinator.assignment.assigned', $assignment, $before, $assignment->only($assignment->getFillable()));

            return $assignment;
        });
    }

    public function revoke(CoordinatorAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment): void {
            $before = $assignment->only($assignment->getFillable());
            $assignment->update(['status' => 'revoked', 'revoked_at' => now()]);
            $this->auditService->record('coordinator.assignment.revoked', $assignment, $before, $assignment->only($assignment->getFillable()));
        });
    }

    public function resetDevice(User $coordinator): void
    {
        $this->deviceService->reset($coordinator);
        $this->auditService->record('coordinator.device.reset', $coordinator, null, ['device_access' => 'revoked']);
    }

    public function requestChange(User $coordinator, Event $event, string $type, string $reason, ?Event $sourceEvent = null): CoordinatorRequest
    {
        return DB::transaction(function () use ($coordinator, $event, $type, $reason, $sourceEvent): CoordinatorRequest {
            if ($type === 'reassign') {
                abort_unless($sourceEvent instanceof Event, 422, 'Choose the event to reassign.');
                abort_unless(
                    CoordinatorAssignment::query()->where('coordinator_id', $coordinator->id)->where('event_id', $sourceEvent->id)->where('status', 'active')->exists(),
                    422,
                    'You can only reassign an active event assignment.',
                );
            }
            $request = CoordinatorRequest::query()->create([
                'coordinator_id' => $coordinator->id,
                'event_id' => $event->id,
                'source_event_id' => $sourceEvent?->id,
                'request_type' => $type,
                'reason' => $reason,
                'status' => 'pending',
            ]);
            $this->auditService->record('coordinator.request.created', $request, null, $request->only($request->getFillable()));

            return $request;
        });
    }

    public function reviewRequest(CoordinatorRequest $request, string $decision, ?string $notes): void
    {
        DB::transaction(function () use ($request, $decision, $notes): void {
            $request->refresh();
            abort_unless($request->status === 'pending', 422, 'This request has already been reviewed.');
            $before = $request->only($request->getFillable());
            $request->update([
                'status' => $decision,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            if ($decision === 'approved') {
                $coordinator = User::query()->findOrFail($request->coordinator_id);
                $event = Event::query()->findOrFail($request->event_id);

                if ($request->request_type === 'remove_event' || $request->request_type === 'reassign') {
                    $assignment = CoordinatorAssignment::query()
                        ->where('coordinator_id', $coordinator->id)
                        ->where('event_id', $request->request_type === 'reassign' ? $request->source_event_id : $event->id)
                        ->first();
                    if ($assignment !== null && $assignment->status === 'active') {
                        $this->revoke($assignment);
                    }
                }

                if ($request->request_type !== 'remove_event') {
                    $this->assign($coordinator, $event);
                }
            }

            $this->auditService->record("coordinator.request.{$decision}", $request, $before, $request->only($request->getFillable()));
        });
    }

    /** @return array<string, mixed> */
    private function safeValues(User $user): array
    {
        return $user->only(['name', 'email', 'role', 'status']);
    }
}
