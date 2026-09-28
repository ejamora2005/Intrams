@props([
    'compact' => false,
    'headingId' => null,
])

@if ($compact)
    <div {{ $attributes->class(['flex items-center gap-3']) }}>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-[10px] font-bold tracking-wide text-blue-800 shadow-sm" aria-hidden="true">SLSU</div>
        <p class="text-xs font-semibold leading-tight tracking-wide text-white">
            <span class="block">INTRAMURALS</span>
            <span class="mt-0.5 block text-blue-200">MANAGEMENT</span>
        </p>
    </div>
@else
    <div {{ $attributes }}>
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-xl bg-blue-700 text-xl font-bold text-white shadow-lg shadow-blue-700/25" aria-hidden="true">SLSU</div>
        <h1 @if ($headingId) id="{{ $headingId }}" @endif class="text-2xl font-semibold tracking-tight text-slate-900">INTRAMURALS MANAGEMENT</h1>
    </div>
@endif
