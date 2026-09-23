<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoordinatorChangeRequest;
use App\Models\Event;
use App\Services\CoordinatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CoordinatorService $coordinatorService)
    {
    }

    public function index(Request $request): View
    {
        $coordinator = $request->user();
        $assignments = $coordinator->coordinatorAssignments()
            ->where('status', 'active')
            ->with('event')
            ->get();
        $events = Event::query()->whereNotIn('status', ['completed', 'cancelled'])->orderBy('name')->get();
        $requests = $coordinator->coordinatorRequests()->with(['event', 'sourceEvent'])->latest()->get();

        return view('coordinator.dashboard', compact('assignments', 'events', 'requests'));
    }

    public function storeRequest(StoreCoordinatorChangeRequest $request): RedirectResponse
    {
        $event = Event::query()->findOrFail($request->integer('event_id'));
        $sourceEvent = $request->filled('source_event_id')
            ? Event::query()->findOrFail($request->integer('source_event_id'))
            : null;
        $this->coordinatorService->requestChange(
            $request->user(),
            $event,
            $request->string('request_type')->value(),
            $request->string('reason')->value(),
            $sourceEvent,
        );

        return back()->with('success', 'Your request was submitted for administrator review.');
    }
}
