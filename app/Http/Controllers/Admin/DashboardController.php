<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                ['label' => 'Active students', 'value' => DB::table('students')->where('status', 'active')->count(), 'route' => 'admin.students.index'],
                ['label' => 'Active editions', 'value' => DB::table('intramural_editions')->where('status', 'active')->count(), 'route' => 'admin.editions.index'],
                ['label' => 'Configured sports', 'value' => DB::table('edition_sports')->whereIn('status', ['preparation', 'active'])->count(), 'route' => 'admin.editions.index'],
                ['label' => 'Scheduled competitions', 'value' => DB::table('competition_schedules')->whereIn('status', ['scheduled', 'live'])->count(), 'route' => 'admin.competition.index'],
            ],
        ]);
    }
}
