@extends('layouts.admin', ['title' => $event->exists ? 'Edit cultural event' : 'Add cultural event', 'subtitle' => $edition->name])

@section('content')
    <x-ops-feedback />
    @php
        $canManage = in_array($edition->status, ['draft', 'active'], true) && $event->status !== 'archived';
        $chosenSystem = old('point_system', $selectedSystem);
        $preview = $pointSystems->get($chosenSystem)['rules'] ?? [];
    @endphp
    <a href="{{ route('admin.cultural.index', ['edition_id' => $edition->id]) }}" class="mb-5 inline-flex text-sm font-semibold text-blue-700">&larr; Back to cultural events</a>
    <form id="cultural-event-form" method="POST" action="{{ $event->exists ? route('admin.cultural.update', $event) : route('admin.cultural.store') }}" data-ops-form class="max-w-4xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        @if ($event->exists) @method('PUT') <input type="hidden" name="event_version" value="{{ old('event_version', $version) }}"> @endif
        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
        @if (! $canManage)<p class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">This event or edition is archived. Restore it before making changes.</p>@endif
        <fieldset @disabled(! $canManage) class="space-y-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700 sm:col-span-2">Event name
                    <input name="name" required maxlength="255" value="{{ old('name', $event->sport?->name) }}" class="mt-1 block w-full rounded-lg border-slate-300">
                    <x-input-error for="name" class="mt-1" />
                </label>
                <label class="block text-sm font-medium text-slate-700">Participation format
                    <select name="participant_type" required class="mt-1 block w-full rounded-lg border-slate-300">
                        @foreach (['team' => 'Faction / team', 'individual' => 'Individual', 'dual' => 'Pair'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('participant_type', $event->participant_type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="participant_type" class="mt-1" />
                </label>
                <label class="block text-sm font-medium text-slate-700">Event status
                    <select name="status" required class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="preparation" @selected(old('status', $event->status) === 'preparation')>Preparation</option>
                        <option value="active" @selected(old('status', $event->status) === 'active')>Active</option>
                    </select>
                    <x-input-error for="status" class="mt-1" />
                </label>
                <label class="block text-sm font-medium text-slate-700 sm:col-span-2">Description <span class="font-normal text-slate-500">(optional)</span>
                    <textarea name="description" rows="3" maxlength="2000" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('description', $event->sport?->description) }}</textarea>
                    <x-input-error for="description" class="mt-1" />
                </label>
            </div>

            <section class="rounded-xl border border-blue-200 p-4 sm:p-5" aria-labelledby="cultural-points-heading">
                <h2 id="cultural-points-heading" class="text-base font-semibold text-slate-900">Linked point system</h2>
                <p class="mt-1 text-sm text-slate-500">Choose an existing point system. Future changes to that system also update this event. Cultural events stay in Cultural when linked to a sports point system.</p>
                <label for="cultural-point-system" class="mt-4 block text-sm font-medium text-slate-700">Point system</label>
                <select id="cultural-point-system" name="point_system" required data-cultural-point-system class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Select a point system</option>
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
                <ul data-cultural-points-preview aria-live="polite" class="mt-2 space-y-2">
                    @foreach ($preview as $rule)
                        <li class="flex justify-between gap-3 text-sm"><span class="text-slate-500">{{ $rule['label'] }}</span><strong class="text-slate-900">{{ number_format($rule['points'], 2) }} pts</strong></li>
                    @endforeach
                </ul>
                @if ($event->exists && $event->sportResult)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p>This event has declared results. Switching point systems recalculates awarded points and standings while keeping winners and the original declaration time.</p>
                        <label class="mt-3 block font-semibold">Admin password
                            <input name="admin_password" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 sm:max-w-sm">
                        </label>
                        <p class="mt-1 text-xs">Required only when changing the awarded point rules.</p>
                        <x-input-error for="admin_password" class="mt-1" />
                    </div>
                @endif
            </section>

            <label class="block text-sm font-medium text-slate-700">Event rules <span class="font-normal text-slate-500">(optional)</span>
                <textarea name="rules" rows="4" maxlength="5000" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('rules', $event->rules) }}</textarea>
                <x-input-error for="rules" class="mt-1" />
            </label>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <button type="submit" data-loading-label="Saving event..." class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">{{ $event->exists ? 'Save cultural event' : 'Create cultural event' }}</button>
                <a href="{{ route('admin.cultural.index', ['edition_id' => $edition->id]) }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            </div>
        </fieldset>
    </form>
@endsection
