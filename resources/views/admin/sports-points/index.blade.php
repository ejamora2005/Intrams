@extends('layouts.admin', [
    'title' => 'Points System',
    'subtitle' => 'Lock and manage the shared point values used for overall standings.',
])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Point rules were not saved.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-2">
        <p class="text-sm text-slate-600">{{ $edition?->name ?? 'No active intramurals edition is available yet.' }}</p>
        <p class="text-sm text-slate-500">Proposal-based sports and cultural groups share point values by category. Saving changes requires the current admin password and recalculates declared results.</p>
    </div>

    @if (! $edition)
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-500">Create or activate an intramurals edition before managing point systems.</div>
    @else
        <section class="grid gap-5 lg:grid-cols-2" aria-labelledby="point-system-heading">
            <div class="lg:col-span-2">
                <p class="ops-eyebrow">Shared scoring</p>
                <h2 id="point-system-heading" class="mt-1 text-lg font-semibold text-slate-950">Points System</h2>
            </div>

            @foreach ($pointSystems as $system)
                @php($pointGroupIcon = $system['key'] === 'sports_athletics' ? 'track-field' : ($system['category'] === 'sports' ? 'team' : 'other'))
                <form method="POST" action="{{ route('admin.sports-points.update', $system['key']) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start">
                        <div class="flex items-start gap-3">
                            <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$pointGroupIcon" /></span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-950">{{ $system['label'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $system['description'] }}</p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ ucfirst($system['category']) }} point group</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $system['configured_count'] }} {{ $system['category'] === 'cultural' ? 'cultural events' : 'sports' }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $system['declared_count'] }} declared</span>
                        </div>
                    </div>

                    @if ($system['configured_count'] > 0)
                        <details class="mt-4 rounded-lg border border-blue-100 bg-blue-50/60">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-blue-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-600">
                                <span>Show included {{ $system['category'] === 'cultural' ? 'cultural events' : 'sports' }}</span>
                                <span class="text-xs font-medium text-blue-700">{{ $system['configured_count'] }} included <span aria-hidden="true">⌄</span></span>
                            </summary>
                            <div class="border-t border-blue-100 px-4 py-3">
                                <ul class="grid gap-2 text-sm text-slate-700 sm:grid-cols-2" aria-label="Included {{ $system['category'] === 'cultural' ? 'cultural events' : 'sports' }}">
                                    @foreach ($system['configured_items'] as $item)
                                        <li class="flex min-w-0 items-start gap-2 rounded-md bg-white px-3 py-2 shadow-sm">
                                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" aria-hidden="true"></span>
                                            <span class="min-w-0"><span class="block truncate font-medium">{{ $item['name'] }}</span>@if ($item['category'])<span class="text-xs text-slate-500">{{ $item['category'] }}</span>@endif</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>
                    @endif

                    @if ($system['configured_count'] === 0)
                        <div class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ $system['empty'] }}</div>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="border-b border-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Placement</th>
                                        <th class="px-3 py-2">Label</th>
                                        <th class="px-3 py-2">Points</th>
                                        <th class="px-3 py-2">Medal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($system['rules'] as $rule)
                                        @php($oldPrefix = 'systems.'.$system['key'].'.placements.'.$rule['placement'])
                                        <tr>
                                            <td class="px-3 py-3 font-semibold text-slate-700">#{{ $rule['placement'] }}</td>
                                            <td class="px-3 py-3">
                                                <input name="systems[{{ $system['key'] }}][placements][{{ $rule['placement'] }}][label]" value="{{ old($oldPrefix.'.label', $rule['label']) }}" required class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                            </td>
                                            <td class="px-3 py-3">
                                                <input name="systems[{{ $system['key'] }}][placements][{{ $rule['placement'] }}][points]" type="number" step="0.01" min="0" value="{{ old($oldPrefix.'.points', $rule['points']) }}" required class="w-32 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                            </td>
                                            <td class="px-3 py-3">
                                                <select name="systems[{{ $system['key'] }}][placements][{{ $rule['placement'] }}][medal]" class="w-36 rounded-lg border-slate-300 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                                    <option value="none" @selected(old($oldPrefix.'.medal', $rule['medal'] ?? 'none') === 'none')>None</option>
                                                    <option value="gold" @selected(old($oldPrefix.'.medal', $rule['medal']) === 'gold')>Gold</option>
                                                    <option value="silver" @selected(old($oldPrefix.'.medal', $rule['medal']) === 'silver')>Silver</option>
                                                    <option value="bronze" @selected(old($oldPrefix.'.medal', $rule['medal']) === 'bronze')>Bronze</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-end sm:justify-between">
                        <label class="block text-sm font-medium text-slate-700">Admin password
                            <input name="systems[{{ $system['key'] }}][admin_password]" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600 sm:w-72">
                        </label>
                        <button @disabled($system['configured_count'] === 0) class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60">Save {{ $system['label'] }}</button>
                    </div>
                </form>
            @endforeach
        </section>
    @endif
@endsection
