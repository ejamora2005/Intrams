<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSchedule;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        return view('admin.competition.index', [
            'schedules' => CompetitionSchedule::with(['editionSport.edition', 'editionSport.sport'])->orderBy('starts_at')->paginate(20),
        ]);
    }
}
