<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSchedule;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    private const SCHEDULE_STATUSES = ['scheduled', 'live', 'completed', 'cancelled'];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        return view('admin.competition.index', [
            'schedules' => CompetitionSchedule::with(['editionSport.edition', 'editionSport.sport', 'bracketMatch'])->orderBy('starts_at')->paginate(20),
            'scheduleStatuses' => self::SCHEDULE_STATUSES,
            'venueOptions' => $this->venueOptions(),
        ]);
    }

    public function updateSchedule(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:starts_at'],
            'venue' => ['required', 'string', Rule::in($this->venueOptions())],
            'status' => ['required', 'in:'.implode(',', self::SCHEDULE_STATUSES)],
        ]);

        $before = $schedule->only(['title', 'starts_at', 'ends_at', 'venue', 'status']);
        $schedule->update([
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'venue' => $data['venue'],
            'status' => $data['status'],
        ]);

        $this->auditService->record('competition_schedule.updated', $schedule, $before, $schedule->only(['title', 'starts_at', 'ends_at', 'venue', 'status']));

        return back()->with('success', 'Schedule updated.');
    }

    public function updateVenue(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'venue' => ['nullable', 'string', Rule::in($this->venueOptions())],
        ]);

        $before = $schedule->only(['venue']);
        $schedule->update([
            'venue' => trim((string) ($data['venue'] ?? '')) ?: 'TBA',
        ]);

        $this->auditService->record('competition_schedule.venue_updated', $schedule, $before, $schedule->only(['venue']));

        return back()->with('success', 'Schedule venue updated.');
    }

    private function venueOptions(): array
    {
        return collect(config('intramurals.venues', ['TBA']))
            ->filter(fn (mixed $venue): bool => is_string($venue) && $venue !== '')
            ->values()
            ->all();
    }
}
