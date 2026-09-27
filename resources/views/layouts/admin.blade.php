<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Admin' }} | Intramurals Management System</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="min-h-screen md:flex md:h-screen md:overflow-hidden">
            <aside class="shrink-0 bg-blue-950 text-blue-100 md:flex md:h-screen md:w-64 md:flex-col md:overflow-y-auto">
                <div class="flex items-center gap-3 border-b border-blue-900 px-5 py-5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-sm font-bold tracking-wide text-blue-800">IM</div>
                    <div>
                        <p class="text-sm font-semibold leading-tight text-white">Intramurals</p>
                        <p class="mt-0.5 text-xs text-blue-300">Administration</p>
                    </div>
                </div>

                <nav class="flex gap-1 overflow-x-auto px-3 py-4 md:flex-col md:overflow-visible" aria-label="Admin navigation">
                    @php
                        $navigation = [
                            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin/dashboard'],
                            ['label' => 'Events / Editions', 'route' => 'admin.editions.index', 'match' => 'admin/editions'],
                            ['label' => 'Students', 'route' => 'admin.students.index', 'match' => 'admin/students'],
                            ['label' => 'Courses', 'route' => 'admin.courses.index', 'match' => 'admin/courses'],
                            ['label' => 'Coordinators', 'route' => 'admin.coordinators.index', 'match' => 'admin/coordinators'],
                            ['label' => 'Teams', 'route' => 'admin.teams.index', 'match' => 'admin/teams'],
                            ['label' => 'Sports', 'route' => 'admin.sports.index', 'match' => 'admin/sports'],
                            ['label' => 'Live Competition', 'route' => 'admin.competition.index', 'match' => 'admin/competition'],
                            ['label' => 'System Logs', 'route' => 'admin.system-logs.index', 'match' => 'admin/system-logs'],
                        ];
                    @endphp
                    @foreach ($navigation as $item)
                        @php($isActive = request()->is($item['match'], $item['match'].'/*'))
                        <a href="{{ route($item['route']) }}" @class([
                            'whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition md:w-full',
                            'bg-blue-700 text-white shadow-sm' => $isActive,
                            'text-blue-200 hover:bg-blue-900 hover:text-white' => !$isActive,
                        ]) @if ($isActive) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="hidden border-t border-blue-900 p-4 md:mt-auto md:block">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 text-xs capitalize text-blue-300">{{ auth()->user()->role }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-blue-200 transition hover:text-white">Sign out</button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 md:h-screen md:overflow-y-auto">
                <header class="border-b border-slate-200 bg-white px-5 py-4 sm:px-8 md:sticky md:top-0 md:z-20">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title ?? 'Admin' }}</h1>
                            @isset($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endisset
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-blue-700 hover:text-blue-900">Sign out</button>
                        </form>
                    </div>
                </header>
                <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">@yield('content')</div>
            </main>
        </div>
        <script>
            document.addEventListener('submit', (event) => {
                const form = event.target;
                const submitter = event.submitter;

                if (!(form instanceof HTMLFormElement) || !form.closest('main')) return;

                form.querySelector('input[name="return_to_module_index"]')?.remove();

                if (submitter && /\bsave\b/i.test(submitter.textContent)) {
                    const redirectIntent = document.createElement('input');
                    redirectIntent.type = 'hidden';
                    redirectIntent.name = 'return_to_module_index';
                    redirectIntent.value = '1';
                    form.appendChild(redirectIntent);
                }
            });
        </script>
    </body>
</html>
