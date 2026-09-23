@extends('layouts.admin', ['title' => 'Add team', 'subtitle' => 'Create a team within an intramurals edition before assigning athletes.'])

@section('content')
    @if ($editions->isEmpty())
        <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
            No intramurals edition exists yet. Create an edition first, then return here to add teams.
        </div>
    @else
        <form method="POST" action="{{ route('admin.teams.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('admin.teams.form', ['submitLabel' => 'Save team'])
        </form>
    @endif
@endsection
