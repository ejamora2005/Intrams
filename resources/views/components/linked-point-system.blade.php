@props(['pointSystems', 'selectedSystem' => null, 'hasResult' => false, 'allowUnlinked' => false, 'currentRules' => []])
@php
    $chosenSystem = old('point_system', $selectedSystem);
    $preview = $pointSystems->get($chosenSystem)['rules'] ?? ($allowUnlinked ? $currentRules : []);
@endphp
<section class="rounded-xl border border-blue-200 p-4 sm:p-5" aria-labelledby="sport-points-heading">
    <h2 id="sport-points-heading" class="text-base font-semibold text-slate-900">Linked point system</h2>
    <p class="mt-1 text-sm text-slate-500">Choose the points awarded for this event. Future changes to the selected point system also update this event. Changing points preserves its participation category and bracket.</p>
    <label for="sport-point-system" class="mt-4 block text-sm font-medium text-slate-700">Point system</label>
    <select id="sport-point-system" name="point_system" @required(! $allowUnlinked) data-sport-point-system class="mt-1 block w-full rounded-lg border-slate-300">
        <option value="" data-point-rules="{{ json_encode($allowUnlinked ? $currentRules : []) }}">{{ $allowUnlinked ? 'Keep current points (not linked)' : 'Select a point system' }}</option>
        @foreach ($pointSystems->groupBy('category') as $category => $options)
            <optgroup label="{{ ucfirst($category) }} point systems">
                @foreach ($options as $option)
                    <option value="{{ $option['key'] }}" data-point-rules="{{ json_encode($option['rules']) }}" @selected($chosenSystem === $option['key'])>{{ $option['label'] }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    <x-input-error for="point_system" class="mt-1" />
    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Scoring preview</p>
    <ul data-sport-points-preview aria-live="polite" class="mt-2 space-y-2">
        @foreach ($preview as $rule)
            <li class="flex justify-between gap-3 text-sm"><span class="text-slate-500">{{ $rule['label'] }}</span><strong class="text-slate-900">{{ number_format($rule['points'], 2) }} pts</strong></li>
        @endforeach
    </ul>
    @if ($hasResult)
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p>This sport has declared results. Changing its point system recalculates awarded points and standings while keeping winners and the original declaration time.</p>
            <label class="mt-3 block font-semibold">Admin password
                <input name="admin_password" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 sm:max-w-sm">
            </label>
            <p class="mt-1 text-xs">Required only when changing the awarded point rules.</p>
            <x-input-error for="admin_password" class="mt-1" />
        </div>
    @endif
</section>
