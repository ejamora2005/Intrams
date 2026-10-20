@extends('layouts.admin', ['title' => $isSchoolActivity ? 'Edit activity schedule' : 'Edit match schedule', 'subtitle' => $isSchoolActivity ? 'Update the school activity date, hours and venue.' : 'Edit exact school dates and hours without changing the bracket result.'])
@section('content')
    @include('admin.schedules._tabs', ['activeTab' => 'matches'])
    <x-ops-feedback />
    <form method="POST" action="{{ route('admin.schedules.matches.update', $schedule) }}" data-ops-form class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
        @csrf @method('PUT')
        <h2 class="text-xl font-semibold text-slate-950">{{ $isSchoolActivity ? ($schedule->title ?: 'School activity') : $schedule->editionSport->sport->name }}</h2>
        <p class="text-sm text-slate-500">{{ $schedule->editionSport->edition->name }} · Philippine time (UTC+8)</p>
        <label class="block text-sm font-medium text-slate-700">Schedule title<input name="title" value="{{ old('title', $schedule->title) }}" maxlength="160" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        @if (!$isSchoolActivity)<label class="block text-sm font-medium text-slate-700">Bracket match
            <select name="bracket_match_id" class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 text-sm">
                <option value="">Standalone schedule</option>
                @foreach ($bracketOptions as $id => $option)<option value="{{ $id }}" @selected((string)old('bracket_match_id', $schedule->bracket_match_id) === (string)$id)>{{ $option['label'] }}</option>@endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">A scored match keeps its original bracket link. Its date, hours and venue remain editable.</span>
        </label>@endif
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['starts_at' => 'Starts', 'ends_at' => 'Ends'] as $field => $label)
                <label class="min-w-0 text-sm font-medium text-slate-700">{{ $label }}<input name="{{ $field }}" type="datetime-local" value="{{ old($field, $schedule->$field?->format('Y-m-d\TH:i')) }}" min="{{ $schedule->editionSport->edition->starts_on->format('Y-m-d') }}T00:00" max="{{ $schedule->editionSport->edition->ends_on->format('Y-m-d') }}T23:59" required class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 text-sm"></label>
            @endforeach
            <label class="block text-sm font-medium text-slate-700">Venue<select name="venue" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">@foreach($venueOptions as $venue)<option value="{{ $venue }}" @selected(old('venue', $schedule->venue ?: 'TBA') === $venue)>{{ $venue }}</option>@endforeach</select></label>
            <label class="block text-sm font-medium text-slate-700">Status<select name="status" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">@foreach($scheduleStatuses as $status)<option value="{{ $status }}" @selected(old('status', $schedule->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
        </div>
        <div class="flex flex-wrap justify-end gap-2"><a href="{{ route('admin.schedules.matches.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Back to schedules</a><button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Save schedule</button></div>
    </form>
@endsection
