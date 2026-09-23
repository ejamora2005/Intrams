<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModuleController extends Controller
{
    /**
     * Display a safe placeholder until the selected management module is implemented.
     */
    public function show(string $module): View
    {
        $modules = [
            'students' => ['title' => 'Students', 'description' => 'Manage student profiles, import records, and event registrations.'],
            'coordinators' => ['title' => 'Coordinators', 'description' => 'Manage coordinator accounts, event assignments, requests, and device access.'],
            'sports-events' => ['title' => 'Sports & Events', 'description' => 'Configure sports, competition events, schedules, fixtures, and scoring rules.'],
            'system-logs' => ['title' => 'System Logs', 'description' => 'Review important account, security, and competition activity across the system.'],
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('admin.module', $modules[$module]);
    }
}
