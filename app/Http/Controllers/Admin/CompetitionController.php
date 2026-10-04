<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSchedule;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        return view('admin.competition.index', [
            'schedules' => CompetitionSchedule::with(['editionSport.edition', 'editionSport.sport', 'bracketMatch'])->orderBy('starts_at')->paginate(20),
        ]);
    }

    public function updateVenue(Request $request, CompetitionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'venue' => ['nullable', 'string', 'max:120'],
        ]);

        $before = $schedule->only(['venue']);
        $schedule->update([
            'venue' => trim((string) ($data['venue'] ?? '')) ?: 'TBA',
        ]);

        $this->auditService->record('competition_schedule.venue_updated', $schedule, $before, $schedule->only(['venue']));

        return back()->with('success', 'Schedule venue updated.');
    }
}
