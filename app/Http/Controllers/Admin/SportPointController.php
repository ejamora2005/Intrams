<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditionSport;
use App\Models\Sport;
use App\Services\AuditService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SportPointController extends Controller
{
    public function __construct(private readonly StandingsService $standingsService) {}

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
                    'configured_items' => $editionSports->map(fn (EditionSport $editionSport): array => [
                        'name' => $editionSport->sport?->name ?? 'Unnamed sport',
                        'code' => $editionSport->sport?->code ?? null,
                        'category' => $editionSport->sport?->participant_category_label ?? null,
                    ])->values()->all(),
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

        DB::transaction(function () use ($editionSports, $system, $placements, $edition): void {
            foreach ($editionSports as $editionSport) {
                $before = $editionSport->scoring_rules;
                $editionSport->update(['scoring_rules' => [...($before ?? []), 'point_system' => $system, 'placements' => $placements]]);
                app(AuditService::class)->record('edition_sport.scoring_rules_updated', $editionSport,
                    ['scoring_rules' => $before], ['scoring_rules' => $editionSport->scoring_rules]);
            }
            $this->standingsService->recalculateTallies($edition);
        });

        return back()->with(
            'success',
            $definition['label'].' was updated for '.$editionSports->count().' configured item(s). Declared points and standings were recalculated; winner placements were kept.',
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

        $events = $edition->editionSports()
            ->with(['sport', 'sportResult'])
            ->whereIn('status', ['preparation', 'active'])
            ->whereHas(
                'sport',
                fn ($query) => $query->whereIn('code', $codes)->where('status', 'active')
            )
            ->get();
        $currentIds = Sport::withoutSupersededOpenSports($events->pluck('sport'))->pluck('id');

        return $events->filter(fn (EditionSport $event): bool => $currentIds->contains($event->sport_id))
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
