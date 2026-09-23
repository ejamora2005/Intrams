@extends('layouts.admin', ['title' => 'Add edition', 'subtitle' => 'Set the school-year period that owns teams, events, and competition records.'])

@section('content')
    <form method="POST" action="{{ route('admin.editions.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @include('admin.editions.form', ['submitLabel' => 'Save edition'])
    </form>
@endsection
