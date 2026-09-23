<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(private readonly EventService $service) {}
    public function index(Request $request): View { $status = $request->string('status')->value() ?: 'all'; $events = Event::query()->with(['sport', 'edition'])->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('starts_at')->paginate(15)->withQueryString(); return view('admin.events.index', compact('events', 'status')); }
    public function create(Request $request): View { $sport = Sport::where('status', 'active')->findOrFail($request->integer('sport_id')); return view('admin.events.create', ['event' => new Event(['sport_id' => $sport->id]), 'sport' => $sport, 'editions' => IntramuralEdition::whereIn('status', ['draft', 'active'])->orderByDesc('starts_on')->get()]); }
    public function store(StoreEventRequest $request): RedirectResponse { $event = $this->service->create($request->validated()); return redirect()->route('admin.events.edit', $event)->with('success', 'Event created as draft.'); }
    public function edit(Event $event): View { return view('admin.events.edit', ['event' => $event, 'sport' => $event->sport, 'editions' => IntramuralEdition::whereIn('status', ['draft', 'active'])->orderByDesc('starts_on')->get()]); }
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse { $this->service->update($event, $request->validated()); return back()->with('success', 'Event updated.'); }
}
