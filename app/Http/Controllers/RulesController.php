<?php

namespace App\Http\Controllers;

use App\Services\EligibilityService;
use App\Services\StandingsService;
use Illuminate\View\View;

class RulesController extends Controller
{
    public function __construct(
        private readonly EligibilityService $eligibilityService,
        private readonly StandingsService $standingsService,
    ) {
    }

    public function admin(): View
    {
        return $this->show('layouts.admin', 'admin.dashboard', 'Admin workspace');
    }

    public function gam(): View
    {
        return $this->show('layouts.coordinator', 'gam.dashboard', 'GAM workspace', request()->user()?->managedTeam, true);
    }

    public function tabulator(): View
    {
        return $this->show('layouts.coordinator', 'tabulator.dashboard', 'Tabulator workspace');
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
        ]);
    }
}
