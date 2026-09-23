<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EditionSportController extends Controller
{
    public function create(IntramuralEdition $edition): View
    {
        return view('admin.editions.sports.create', compact('edition'));
    }

    public function store(Request $request, IntramuralEdition $edition): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'participant_type' => ['required', 'in:team,dual,individual'],
            'game_mechanic' => ['required', 'in:single_elimination,double_elimination,round_robin,custom'],
            'rules' => ['nullable', 'string', 'max:5000'],
        ]);
        $name = Str::headline(trim($data['name']));
        $sport = Sport::firstOrCreate(['name' => $name], ['code' => 'SPORT-'.Str::upper(Str::random(8)), 'description' => $data['description'] ?? null, 'status' => 'active', 'is_system' => false]);
        abort_if(EditionSport::where('edition_id', $edition->id)->where('sport_id', $sport->id)->exists(), 422, 'This sport is already configured for the edition.');
        $editionSport = EditionSport::create(['edition_id' => $edition->id, 'sport_id' => $sport->id, 'participant_type' => $data['participant_type'], 'game_mechanic' => $data['game_mechanic'], 'rules' => $data['rules'] ?? null, 'status' => 'preparation']);
        app(AuditService::class)->record('edition_sport.created', $editionSport, null, $editionSport->only($editionSport->getFillable()));

        return redirect()->route('admin.editions.index')->with('success', "{$sport->name} was configured for this edition.");
    }
}
