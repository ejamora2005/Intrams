<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCoordinatorRequest;
use App\Http\Requests\StoreCoordinatorAssignmentRequest;
use App\Http\Requests\StoreCoordinatorRequest;
use App\Http\Requests\UpdateCoordinatorRequest;
use App\Models\CoordinatorAssignment;
use App\Models\CoordinatorRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\CoordinatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function __construct(private readonly CoordinatorService $coordinatorService)
    {
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->value();
        $status = $request->string('status')->value() ?: 'active';
        $coordinators = User::query()
            ->where('role', 'coordinator')
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->withCount(['coordinatorAssignments as active_assignment_count' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.coordinators.index', compact('coordinators', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.coordinators.create', ['coordinator' => new User()]);
    }

    public function store(StoreCoordinatorRequest $request): RedirectResponse
    {
        $coordinator = $this->coordinatorService->create($request->validated());

        return redirect()->route('admin.coordinators.edit', $coordinator)->with('success', 'Coordinator account created. Assign events below.');
    }

    public function edit(User $coordinator): View
    {
        $this->ensureCoordinator($coordinator);
        $coordinator->load([
            'coordinatorAssignments.event',
            'coordinatorDevices' => fn ($query) => $query->latest('last_seen_at'),
            'coordinatorRequests.event',
            'coordinatorRequests.sourceEvent',
        ]);
        $events = Event::query()->whereNotIn('status', ['completed', 'cancelled'])->orderBy('name')->get();

        return view('admin.coordinators.edit', compact('coordinator', 'events'));
    }

    public function update(UpdateCoordinatorRequest $request, User $coordinator): RedirectResponse
    {
        $this->ensureCoordinator($coordinator);
        $this->coordinatorService->update($coordinator, $request->validated());

        return back()->with('success', 'Coordinator account updated.');
    }

    public function assign(StoreCoordinatorAssignmentRequest $request, User $coordinator): RedirectResponse
    {
        $this->ensureCoordinator($coordinator);
        $event = Event::query()->findOrFail($request->integer('event_id'));
        $this->coordinatorService->assign($coordinator, $event);

        return back()->with('success', 'Event assignment saved.');
    }

    public function revoke(User $coordinator, CoordinatorAssignment $assignment): RedirectResponse
    {
        $this->ensureCoordinator($coordinator);
        abort_unless($assignment->coordinator_id === $coordinator->id, 404);
        $this->coordinatorService->revoke($assignment);

        return back()->with('success', 'Event assignment revoked.');
    }

    public function resetDevice(User $coordinator): RedirectResponse
    {
        $this->ensureCoordinator($coordinator);
        $this->coordinatorService->resetDevice($coordinator);

        return back()->with('success', 'Trusted device reset. The coordinator can register one new device at their next login.');
    }

    public function reviewRequest(ReviewCoordinatorRequest $request, CoordinatorRequest $coordinatorRequest): RedirectResponse
    {
        $this->coordinatorService->reviewRequest(
            $coordinatorRequest,
            $request->string('decision')->value(),
            $request->string('review_notes')->value() ?: null,
        );

        return back()->with('success', 'Coordinator request reviewed.');
    }

    private function ensureCoordinator(User $user): void
    {
        abort_unless($user->role === 'coordinator', 404);
    }
}
