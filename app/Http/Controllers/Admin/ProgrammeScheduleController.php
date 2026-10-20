<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePublicScheduleRequest;
use App\Models\IntramuralEdition;
use App\Services\PublicProgrammeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgrammeScheduleController extends Controller
{
    public function __construct(private readonly PublicProgrammeService $programmeService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'category' => ['nullable', Rule::in(['sports', 'cultural', 'activities'])],
        ]);
        $edition = $this->activeEdition();
        $available = $edition && $this->programmeService->appliesTo($edition);
        $allEvents = $available ? $this->programmeService->eventsFor($edition) : collect();
        $teamOptions = $this->programmeService->teamOptions($edition);
        $allEvents = $allEvents->map(fn (array $event): array => [...$event, 'matchups' => $this->programmeService->matchupsFor($event['games'] ?? [], $teamOptions)]);
        $search = trim($filters['search'] ?? '');
        $date = $filters['date'] ?? '';
        $category = $filters['category'] ?? '';
        $events = $allEvents
            ->when($search !== '', fn ($items) => $items->filter(fn (array $event): bool => Str::contains(Str::lower($event['event'].' '.($event['venue'] ?? '')), Str::lower($search))))
            ->when($date !== '', fn ($items) => $items->where('date', $date))
            ->when($category !== '', fn ($items) => $items->where('category', $category));

        return view('admin.schedules.index', [
            'edition' => $edition, 'available' => $available, 'eventsByDate' => $events->groupBy('date'),
            'total' => $allEvents->count(), 'visibleCount' => $events->count(),
            'dates' => $allEvents->pluck('date')->unique()->sort()->values(),
            'search' => $search, 'date' => $date, 'category' => $category,
            'teamOptions' => $teamOptions,
        ]);
    }

    public function update(UpdatePublicScheduleRequest $request, string $entryKey): RedirectResponse
    {
        $edition = $this->activeEdition();
        abort_unless($edition && $edition->id === $request->integer('edition_id') && $this->programmeService->appliesTo($edition), 404);
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        if ($request->boolean('games_present') || $request->has('games')) {
            $data['games'] = array_values($data['games'] ?? []);
        }
        $updated = $this->programmeService->updateEvent($edition, $entryKey,
            Arr::only($data, ['event', 'date', 'session', 'starts_at', 'ends_at', 'category', 'venue', 'is_published', 'games']),
            (int) $data['version'], $request->user());

        return redirect()->to(route('admin.schedules.index', ['date' => $updated['date']]).'#schedule-'.$entryKey)
            ->with('success', 'Schedule updated. Published changes are now visible on the public schedule.');
    }

    private function activeEdition(): ?IntramuralEdition
    {
        return IntramuralEdition::where('status', 'active')->orderByDesc('starts_on')->orderByDesc('id')->first();
    }
}
