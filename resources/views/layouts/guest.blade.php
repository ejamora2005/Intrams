@props(['livewire' => true])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.display_name', 'SLSU INTRAMURALS') }}</title>
        <x-pwa-meta />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')

        <!-- Styles -->
        @if ($livewire) @livewireStyles @endif
    </head>
    <body class="bg-slate-50">
        <div class="font-sans text-slate-900 antialiased">
            {{ $slot }}
        </div>

        @if ($livewire) @livewireScripts @endif
        <x-pwa-install />
    </body>
</html>
