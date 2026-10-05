@extends('layouts.admin', ['title' => 'Edit student', 'subtitle' => 'Update the athlete record while keeping a secure activity history.'])

@section('content')
    @if (session('success'))
        <div class="mb-5 max-w-3xl rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 max-w-3xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @method('PUT')
        @include('admin.students.form', ['submitLabel' => 'Save changes'])
    </form>
    @if ($edition)
        <section class="mt-6 max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-base font-semibold text-slate-900">Faction assignment</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $edition->name }}: {{ $currentTeam?->name ?? 'Unassigned' }}</p>
            <form method="POST" action="{{ route('admin.students.team.update', $student) }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                @method('PUT')
                <div class="min-w-52 flex-1">
                    <label for="team_id" class="text-sm font-medium text-slate-700">Faction</label>
                    <select id="team_id" name="team_id" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select faction</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected((string) old('team_id', $currentTeam?->id) === (string) $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">{{ $currentTeam ? 'Transfer student' : 'Assign faction' }}</button>
            </form>
        </section>
    @endif
@endsection
