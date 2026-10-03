<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditionSport;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SportPointController extends Controller
{
    private const CATEGORIES = [
        'sports' => [
            'label' => 'Sports Point System',
            'description' => 'Applied to every regular athletic sport.',
            'empty' => 'No regular sports are configured for the active edition.',
        ],
        'cultural' => [
            'label' => 'Cultural Point System',
            'description' => 'Applied to every cultural event and bonus award.',
            'empty' => 'No cultural scoring items are configured for the active edition.',
        ],
    ];

    public function __construct(private readonly StandingsService $standingsService)
    {
    }

    public function index(): View
    {
        $edition = $this->standingsService->activeEdition();
        $pointSystems = collect(self::CATEGORIES)
            ->map(function (array $category, string $key) use ($edition): array {
                $editionSports = $this->editionSportsForCategory($edition, $key);
                $referenceSport = $editionSports->first();

                return [
                    ...$category,
                    'key' => $key,
                    'configured_count' => $editionSports->count(),
                    'declared_count' => $editionSports
                        ->filter(fn (EditionSport $editionSport): bool => $editionSport->sportResult !== null)
                        ->count(),
                    'rules' => $referenceSport instanceof EditionSport
                        ? $this->standingsService->pointRulesFor($referenceSport)
                        : StandingsService::DEFAULT_PLACEMENTS,
                ];
            })
            ->values();

        return view('admin.sports-points.index', [
            'edition' => $edition,
            'pointSystems' => $pointSystems,
        ]);
    }

    public function update(Request $request, string $category): RedirectResponse
    {
        abort_unless(array_key_exists($category, self::CATEGORIES), 404);

        $edition = $this->standingsService->activeEdition();
        abort_unless($edition?->status === 'active', 404);

        $data = $request->validate([
            "systems.{$category}.admin_password" => ['required', 'string'],
            "systems.{$category}.placements" => ['required', 'array', 'min:1'],
            "systems.{$category}.placements.*.label" => ['required', 'string', 'max:60'],
            "systems.{$category}.placements.*.points" => ['required', 'numeric', 'min:0', 'max:999999.99'],
            "systems.{$category}.placements.*.medal" => ['nullable', 'in:gold,silver,bronze,none'],
        ]);

        $system = $data['systems'][$category];

        if (! Hash::check($system['admin_password'], (string) $request->user()?->password)) {
            throw ValidationException::withMessages([
                "systems.{$category}.admin_password" => 'The admin password did not match.',
            ]);
        }

        $placements = collect($system['placements'])
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

        $editionSports = $this->editionSportsForCategory($edition, $category);
        abort_if($editionSports->isEmpty(), 422, self::CATEGORIES[$category]['empty']);

        $editionSports->each(
            fn (EditionSport $editionSport) => $editionSport->update(['scoring_rules' => ['placements' => $placements]])
        );
        $this->standingsService->recalculateTallies($edition);

        return back()->with(
            'success',
            self::CATEGORIES[$category]['label'].' was updated for '.$editionSports->count().' configured item(s).',
        );
    }

    private function editionSportsForCategory(mixed $edition, string $category)
    {
        if ($edition === null) {
            return collect();
        }

        return $edition->editionSports()
            ->with(['sport', 'sportResult'])
            ->whereHas(
                'sport',
                fn ($query) => $category === 'cultural'
                    ? $query->where('code', 'like', 'CULT-%')
                    : $query->where('code', 'not like', 'CULT-%')
            )
            ->get()
            ->sortBy(fn (EditionSport $editionSport): string => $editionSport->sport?->name ?? '')
            ->values();
    }
}
