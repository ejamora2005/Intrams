<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditionSport;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SportPointController extends Controller
{
    public function __construct(private readonly StandingsService $standingsService)
    {
    }

    public function index(): View
    {
        $edition = $this->standingsService->activeEdition();
        $pointSystems = $this->pointSystemDefinitions()
            ->map(function (array $definition, string $key) use ($edition): array {
                $editionSports = $this->editionSportsForSystem($edition, $definition);
                $referenceSport = $editionSports->first();

                return [
                    ...$definition,
                    'key' => $key,
                    'configured_count' => $editionSports->count(),
                    'declared_count' => $editionSports
                        ->filter(fn (EditionSport $editionSport): bool => $editionSport->sportResult !== null)
                        ->count(),
                    'rules' => $referenceSport instanceof EditionSport
                        ? $this->standingsService->pointRulesFor($referenceSport)
                        : $this->normalizePlacements($definition['placements'] ?? StandingsService::DEFAULT_PLACEMENTS),
                ];
            })
            ->values();

        return view('admin.sports-points.index', [
            'edition' => $edition,
            'pointSystems' => $pointSystems,
        ]);
    }

    public function update(Request $request, string $system): RedirectResponse
    {
        $definition = $this->pointSystemDefinitions()->get($system);
        abort_unless($definition !== null, 404);

        $edition = $this->standingsService->activeEdition();
        abort_unless($edition?->status === 'active', 404);

        $data = $request->validate([
            "systems.{$system}.admin_password" => ['required', 'string'],
            "systems.{$system}.placements" => ['required', 'array', 'min:1'],
            "systems.{$system}.placements.*.label" => ['required', 'string', 'max:60'],
            "systems.{$system}.placements.*.points" => ['required', 'numeric', 'min:0', 'max:999999.99'],
            "systems.{$system}.placements.*.medal" => ['nullable', 'in:gold,silver,bronze,none'],
        ]);

        $payload = $data['systems'][$system];

        if (! Hash::check($payload['admin_password'], (string) $request->user()?->password)) {
            throw ValidationException::withMessages([
                "systems.{$system}.admin_password" => 'The admin password did not match.',
            ]);
        }

        $placements = collect($payload['placements'])
            ->map(function (array $rule, int|string $placement): array {
                $medal = $rule['medal'] ?? null;

                return [
                    'placement' => (int) $placement,
                    'label' => trim($rule['label']),
                    'points' => round((float) $rule['points'], 2),
                    'medal' => $medal === 'none' ? null : $medal,
                ];
            })
            ->filter(fn (array $rule): bool => $rule['placement'] > 0)
            ->sortBy('placement')
            ->values()
            ->all();

        $editionSports = $this->editionSportsForSystem($edition, $definition);
        abort_if($editionSports->isEmpty(), 422, $definition['empty']);

        $editionSports->each(
            fn (EditionSport $editionSport) => $editionSport->update(['scoring_rules' => ['point_system' => $system, 'placements' => $placements]])
        );
        $this->standingsService->recalculateTallies($edition);

        return back()->with(
            'success',
            $definition['label'].' was updated for '.$editionSports->count().' configured item(s).',
        );
    }

    private function pointSystemDefinitions()
    {
        return collect(config('intramurals.point_systems', []))
            ->map(function (array $definition, string $key): array {
                $label = $definition['label'] ?? Str::headline($key);

                return [
                    'label' => $label,
                    'category' => $definition['category'] ?? 'sports',
                    'description' => $definition['description'] ?? 'Proposal-based point system.',
                    'empty' => $definition['empty'] ?? 'No configured items use this point system yet.',
                    'codes' => $definition['codes'] ?? [],
                    'placements' => $this->normalizePlacements($definition['placements'] ?? StandingsService::DEFAULT_PLACEMENTS),
                ];
            });
    }

    private function editionSportsForSystem(mixed $edition, array $definition)
    {
        if ($edition === null) {
            return collect();
        }

        $codes = collect($definition['codes'] ?? [])
            ->filter()
            ->values()
            ->all();

        if ($codes === []) {
            return collect();
        }

        return $edition->editionSports()
            ->with(['sport', 'sportResult'])
            ->whereHas(
                'sport',
                fn ($query) => $query->whereIn('code', $codes)
            )
            ->get()
            ->sortBy(fn (EditionSport $editionSport): string => $editionSport->sport?->name ?? '')
            ->values();
    }

    private function normalizePlacements(array $placements): array
    {
        return collect($placements)
            ->map(function (array $rule, int|string $key): array {
                $placement = (int) ($rule['placement'] ?? $key);

                return [
                    'placement' => $placement,
                    'label' => (string) ($rule['label'] ?? 'Place '.$placement),
                    'points' => (float) ($rule['points'] ?? 0),
                    'medal' => $rule['medal'] ?? null,
                ];
            })
            ->filter(fn (array $rule): bool => $rule['placement'] > 0)
            ->sortBy('placement')
            ->values()
            ->all();
    }
}
