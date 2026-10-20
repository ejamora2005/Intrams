@props(['schedule', 'returnTo' => 'competition', 'label' => 'Delete', 'scheduleName' => null])
@php
    $scheduleName ??= $schedule->title ?: (($schedule->editionSport?->sport?->name ?? 'Competition').' · '.($schedule->bracket_match_id ? 'Game '.$schedule->bracketMatch?->match_number : 'Schedule'));
@endphp
<button type="button" data-delete-schedule data-delete-url="{{ route('admin.schedules.matches.destroy', $schedule) }}" data-delete-label="{{ $scheduleName }}" data-delete-when="{{ $schedule->starts_at->format('M j, Y g:i A') }}" data-delete-return-to="{{ $returnTo }}" aria-label="Delete schedule for {{ $scheduleName }}" {{ $attributes->class(['inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v5m4-5v5" /></svg>
    {{ $label }}
</button>
