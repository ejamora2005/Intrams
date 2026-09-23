<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParticipationRuleRequest;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\IntramuralEdition;
use App\Models\ParticipationRule;
use App\Models\Student;
use App\Models\Team;
use App\Services\ParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly ParticipationService $service) {}

    public function index(Request $request): View
    {
        $registrations = EventRegistration::with(['event.sport', 'student', 'team'])->latest('registered_at')->paginate(20);

        return view('admin.registrations.index', compact('registrations'));
    }

    public function create(): View
    {
        $events = Event::with(['sport', 'edition'])->where('status', 'scheduled')->orderBy('name')->get();
        $students = Student::where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get();
        $teams = Team::with(['members.student' => fn ($query) => $query->where('status', 'active')])->where('status', 'active')->orderBy('name')->get();

        return view('admin.registrations.create', [
            'events' => $events,
            'eventOptions' => $events->map(fn ($event) => ['id' => $event->id, 'sport_id' => $event->sport_id, 'edition_id' => $event->edition_id, 'name' => $event->name, 'type' => $event->competition_type])->values(),
            'students' => $students,
            'studentOptions' => $students->map(fn ($student) => ['id' => $student->id, 'name' => $student->full_name, 'number' => $student->student_number])->values(),
            'teams' => $teams,
            'teamOptions' => $teams->map(fn ($team) => ['id' => $team->id, 'edition_id' => $team->edition_id, 'name' => $team->name, 'student_ids' => $team->members->pluck('student_id')->values()])->values(),
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $event = Event::findOrFail($request->integer('event_id'));
        $students = Student::whereIn('id', $request->validated('student_ids'))->get();
        $team = $request->filled('team_id') ? Team::findOrFail($request->integer('team_id')) : null;
        $registrations = $this->service->registerMany($event, $students, $team);

        return redirect()->route('admin.registrations.index')->with('success', $registrations->count() === 1 ? 'Athlete added to the event.' : 'Athletes added to the event.');
    }

    public function withdraw(EventRegistration $registration): RedirectResponse
    {
        $this->service->withdraw($registration);

        return back()->with('success', 'Athlete withdrawn from the event.');
    }

    public function rules(): View
    {
        return view('admin.registrations.rules', ['rules' => ParticipationRule::with('edition')->latest()->get(), 'editions' => IntramuralEdition::whereIn('status', ['draft', 'active'])->get()]);
    }

    public function storeRule(StoreParticipationRuleRequest $request): RedirectResponse
    {
        $this->service->createRule($request->validated());

        return back()->with('success', 'Participation rule saved.');
    }
}
