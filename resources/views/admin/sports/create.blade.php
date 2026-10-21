@extends('layouts.admin', ['title' => 'Add sport', 'subtitle' => $edition->name])
@section('content')
    <x-ops-feedback />
    <a href="{{ route('admin.sports.index', ['edition_id' => $edition->id]) }}" class="mb-5 inline-flex text-sm font-semibold text-blue-700">&larr; Back to sports</a>
    <form id="sport-event-form" method="POST" action="{{ route('admin.sports.store') }}" data-ops-form class="max-w-4xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
        <input type="hidden" name="return_to_module_index" value="1">
        @include('admin.sports.form', ['submitLabel' => 'Create sport'])
    </form>
@endsection
