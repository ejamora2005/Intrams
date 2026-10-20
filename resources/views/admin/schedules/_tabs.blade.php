<nav aria-label="Schedule sections" class="mb-5 flex flex-wrap gap-2">
    <a href="{{ route('admin.schedules.index') }}" @if ($activeTab === 'public') aria-current="page" @endif class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'public' ? 'bg-blue-700 text-white' : 'bg-white text-slate-700' }}">Public timetable</a>
    <a href="{{ route('admin.schedules.matches.index') }}" @if ($activeTab === 'matches') aria-current="page" @endif class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold {{ $activeTab === 'matches' ? 'bg-blue-700 text-white' : 'bg-white text-slate-700' }}">Bracket / event schedules</a>
</nav>
