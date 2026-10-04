@props([
    'compact' => false,
    'headingId' => null,
])

@php
    $appName = 'INTRAMURAL MS';
    $logoSource = asset(config('landing.appLogo'));
@endphp

@if ($compact)
    <div {{ $attributes->class(['flex items-center gap-3']) }}>
        <img src="{{ $logoSource }}" alt="" width="56" height="52" class="h-12 w-12 shrink-0 object-contain drop-shadow-[0_8px_18px_rgba(0,0,0,0.35)]" decoding="async">
        <p class="text-xs font-semibold leading-tight tracking-wide text-white">
            <span class="block">INTRAMURAL</span>
            <span class="mt-0.5 block text-blue-200">MS</span>
        </p>
    </div>
@else
    <div {{ $attributes }}>
        <img src="{{ $logoSource }}" alt="" width="144" height="132" class="mx-auto mb-5 h-24 w-auto max-w-[10rem] object-contain drop-shadow-[0_14px_28px_rgba(15,23,42,0.22)]" decoding="async">
        <h1 @if ($headingId) id="{{ $headingId }}" @endif class="text-2xl font-semibold tracking-tight text-slate-900">{{ $appName }}</h1>
    </div>
@endif
