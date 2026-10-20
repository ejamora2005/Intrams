@if (session('participation_warning'))
    <div role="status" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p>{{ session('participation_warning') }}</p>
        <a href="{{ route(auth()->user()?->role === 'gam' ? 'gam.possible-dq.index' : 'admin.possible-dq.index') }}" class="mt-2 inline-block font-semibold underline">Open Possible DQ Watchlist</a>
    </div>
@endif
