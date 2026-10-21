@extends('layouts.admin', ['title' => 'Edit sport', 'subtitle' => $edition->name])
@section('content')
    <x-ops-feedback />
    @php($canManage = in_array($edition->status, ['draft', 'active'], true) && $editionSport->status !== 'archived')
    <a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="mb-5 inline-flex text-sm font-semibold text-blue-700">&larr; Back to sports</a>
    <form id="sport-event-form" method="POST" action="{{ route('admin.sports.update', $sport) }}" data-ops-form class="max-w-4xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @method('PUT')
        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
        <input type="hidden" name="return_to_module_index" value="1">
        <input type="hidden" name="event_version" value="{{ old('event_version', $version) }}">
        @if (! $canManage)<p class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">This event or edition is archived. Restore it before making changes.</p>@endif
        <fieldset @disabled(! $canManage)>
            @include('admin.sports.form', ['submitLabel' => 'Save sport'])
        </fieldset>
    </form>
@endsection
