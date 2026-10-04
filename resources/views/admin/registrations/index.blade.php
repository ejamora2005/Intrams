@extends('layouts.admin', ['title' => 'Athletes', 'subtitle' => 'Add athletes to scheduled events and retain participation history.'])

@section('content')
    <div class="flex justify-between">
        <a href="{{ route('admin.participation-rules.index') }}" class="rounded-lg border px-4 py-2 text-sm">Participation rules</a>
        <a href="{{ route('admin.registrations.create') }}" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Add athlete</a>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border bg-white">
        <table class="min-w-full divide-y text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left">Student</th>
                    <th class="px-5 py-3 text-left">Event</th>
                    <th class="px-5 py-3 text-left">Team</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($registrations as $registration)
                    <tr>
                        <td class="px-5 py-4">{{ $registration->student->full_name }}</td>
                        <td class="px-5 py-4">{{ $registration->event->name }}</td>
                        <td class="px-5 py-4">
                            @if ($registration->team)
                                <x-team-badge :team="$registration->team" class="font-medium text-slate-900" />
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">{{ ucfirst($registration->status) }}</td>
                        <td class="px-5 py-4">
                            @if ($registration->status === 'active')
                                <form method="POST" action="{{ route('admin.registrations.withdraw', $registration) }}">
                                    @csrf
                                    <button class="text-red-700">Withdraw</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center">No athletes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $registrations->links() }}
    </div>
@endsection
