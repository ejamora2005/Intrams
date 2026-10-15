<?php

namespace App\Http\Controllers;

use App\Services\EligibilityService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RulesController extends Controller
{
    public function __construct(
        private readonly EligibilityService $eligibilityService,
        private readonly StandingsService $standingsService,
    ) {}

    public function admin(): View
    {
        return $this->show('layouts.admin', 'admin.dashboard', 'Admin workspace');
    }

    public function gam(): View
    {
        return $this->show('layouts.coordinator', 'gam.dashboard', 'GAM workspace', request()->user()?->managedTeam);
    }

    public function tabulator(): View
    {
        return $this->show('layouts.coordinator', 'tabulator.dashboard', 'Tabulator workspace');
    }

    public function possibleDqAdmin(): View
    {
        return $this->possibleDq('layouts.admin', 'admin.dashboard', 'Admin workspace', 'admin.rules.index');
    }

    public function possibleDqGam(): View
    {
        return $this->possibleDq('layouts.coordinator', 'gam.dashboard', 'GAM workspace', 'gam.rules.index', request()->user()?->managedTeam);
    }

    public function possibleDqTabulator(): View
    {
        return $this->possibleDq('layouts.coordinator', 'tabulator.dashboard', 'Tabulator workspace', 'tabulator.rules.index');
    }

    public function repair(Request $request): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:REPAIR']]);

        $edition = $this->standingsService->activeEdition();
        abort_unless($edition, 422, 'No active intramurals edition is available.');

        $removed = $this->eligibilityService->removeDuplicateOpenRegistrations($edition);

        return redirect()
            ->route('admin.rules.index')
            ->with('success', $removed === 0
                ? 'No duplicate open registrations needed to be removed.'
                : $removed.' duplicate open registration(s) removed and affected brackets reset.');
    }

    private function show(string $layout, string $homeRoute, string $workspaceLabel, $managedTeam = null, bool $requireManagedTeam = false): View
    {
        $edition = $this->standingsService->activeEdition();
        $flaggedStudents = collect();
        if ($edition && (! $requireManagedTeam || $managedTeam)) {
            $flaggedStudents = $this->eligibilityService->flaggedStudents($edition, $managedTeam);
        }

        return view('rules.index', [
            'layout' => $layout,
            'homeRoute' => $homeRoute,
            'workspaceLabel' => $workspaceLabel,
            'edition' => $edition,
            'maxEvents' => $this->eligibilityService->maxEvents(),
            'individualDualOnlyMaxEvents' => $this->eligibilityService->individualDualOnlyMaxEvents(),
            'allowedCombinations' => $this->eligibilityService->rulesForDisplay(),
            'medicalCertificateExemptions' => config('intramurals.medical_certificate.exempt_sport_codes', []),
            'managedTeam' => $managedTeam,
            'flaggedStudents' => $flaggedStudents,
            'canRepairRegistrations' => request()->user()?->role === 'admin',
            'duplicateOpenRegistrationCount' => $edition && request()->user()?->role === 'admin'
                ? $this->eligibilityService->duplicateOpenRegistrationCount($edition)
                : 0,
        ]);
    }

    private function possibleDq(string $layout, string $homeRoute, string $workspaceLabel, string $rulesRoute, $managedTeam = null): View
    {
        $edition = $this->standingsService->activeEdition();
        $flaggedStudents = $edition
            ? $this->eligibilityService->flaggedStudents($edition, $managedTeam)
            : collect();

        return view('rules.possible-dq', [
            'layout' => $layout,
            'homeRoute' => $homeRoute,
            'workspaceLabel' => $workspaceLabel,
            'rulesRoute' => $rulesRoute,
            'edition' => $edition,
            'managedTeam' => $managedTeam,
            'flaggedStudents' => $flaggedStudents,
        ]);
    }
}
