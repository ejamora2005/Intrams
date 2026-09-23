<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEditionRequest;
use App\Http\Requests\UpdateEditionRequest;
use App\Models\IntramuralEdition;
use App\Services\EditionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditionController extends Controller
{
    public function __construct(private readonly EditionService $editionService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->value() ?: 'all';
        $search = $request->string('search')->value();
        $editions = IntramuralEdition::query()
            ->withCount('teams')
            ->when(in_array($status, ['draft', 'active', 'closed', 'archived'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($editionQuery) => $editionQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('school_year', 'like', "%{$search}%")))
            ->orderByDesc('starts_on')
            ->paginate(15)
            ->withQueryString();

        return view('admin.editions.index', compact('editions', 'status', 'search'));
    }

    public function create(): View
    {
        return view('admin.editions.create', ['edition' => new IntramuralEdition()]);
    }

    public function store(StoreEditionRequest $request): RedirectResponse
    {
        $edition = $this->editionService->create($request->validated());

        return redirect()->route('admin.editions.index')->with('success', "{$edition->name} was created.");
    }

    public function edit(IntramuralEdition $edition): View
    {
        $edition->loadCount('teams')->load(['editionSports.sport']);

        return view('admin.editions.edit', compact('edition'));
    }

    public function update(UpdateEditionRequest $request, IntramuralEdition $edition): RedirectResponse
    {
        $this->editionService->update($edition, $request->validated());

        return redirect()->route('admin.editions.index')->with('success', "{$edition->name} was updated.");
    }

    public function destroy(IntramuralEdition $edition): RedirectResponse
    {
        $name = $edition->name;
        $this->editionService->archive($edition);

        return redirect()->route('admin.editions.index')->with('success', "{$name} was archived. Its teams and historical records were preserved.");
    }
}
