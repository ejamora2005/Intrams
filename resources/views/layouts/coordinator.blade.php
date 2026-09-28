<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Coordinator' }} | INTRAMURALS MANAGEMENT</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-blue-900 bg-blue-950 text-white shadow-lg shadow-blue-950/10">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('coordinator.dashboard') }}" aria-label="Coordinator dashboard" class="min-w-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <x-intramurals-brand compact />
                    <span class="mt-2 block text-xs font-medium uppercase tracking-[0.14em] text-blue-300">Coordinator workspace</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button class="rounded-lg border border-blue-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-300 sm:px-4">Sign out</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">@yield('content')</main>
    </body>
</html>
