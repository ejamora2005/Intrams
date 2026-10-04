<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Admin' }} | INTRAMURALS MANAGEMENT</title>
        <x-pwa-meta />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ops-body bg-blue-950 font-sans text-slate-900 antialiased" style="--ops-background: url('{{ asset(config('landing.background')) }}')">
        @php
            $themeTitle = config('intramurals.theme.title');
            $themeTagline = config('intramurals.theme.tagline');
        @endphp

        <div class="min-h-screen md:flex md:h-screen md:overflow-hidden">
            <div data-admin-sidebar-backdrop class="fixed inset-0 z-40 hidden bg-slate-950/55 backdrop-blur-sm md:hidden" aria-hidden="true"></div>

            <aside id="admin-sidebar" data-admin-sidebar class="ops-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(18rem,calc(100vw-3rem))] -translate-x-full flex-col overflow-hidden bg-blue-950 text-blue-100 shadow-2xl transition-transform duration-200 ease-out md:static md:z-auto md:h-screen md:w-64 md:translate-x-0 md:shadow-none" aria-label="Admin sidebar">
                <div class="flex items-center border-b border-blue-900">
                    <a href="{{ route('admin.dashboard') }}" aria-label="INTRAMURALS MANAGEMENT dashboard" class="min-w-0 flex-1 px-5 py-5 transition hover:bg-blue-900/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-400">
                        <x-intramurals-brand compact />
                    </a>
                    <button type="button" data-close-admin-sidebar class="mr-3 rounded-lg p-2 text-blue-200 transition hover:bg-blue-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 md:hidden" aria-label="Close navigation">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto px-3 py-4" aria-label="Admin navigation">
                    @php
                        $navigation = [
                            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin/dashboard'],
                            ['label' => 'Events / Editions', 'route' => 'admin.editions.index', 'match' => 'admin/editions'],
                            ['label' => 'Students', 'route' => 'admin.students.index', 'match' => 'admin/students'],
                            ['label' => 'Courses', 'route' => 'admin.courses.index', 'match' => 'admin/courses'],
                            ['label' => 'Coordinators', 'route' => 'admin.coordinators.index', 'match' => 'admin/coordinators'],
                            ['label' => 'Operations Accounts', 'route' => 'admin.operations-accounts.index', 'match' => 'admin/operations-accounts'],
                            ['label' => 'Teams', 'route' => 'admin.teams.index', 'match' => 'admin/teams'],
                            ['label' => 'Sports', 'route' => 'admin.sports.index', 'match' => 'admin/sports'],
                            ['label' => 'Cultural', 'route' => 'admin.cultural.index', 'match' => 'admin/cultural'],
                            ['label' => 'Points System', 'route' => 'admin.sports-points.index', 'match' => 'admin/sports-points'],
                            ['label' => 'Rules & Guidelines', 'route' => 'admin.rules.index', 'match' => 'admin/rules-guidelines'],
                            ['label' => 'Live Competition', 'route' => 'admin.competition.index', 'match' => 'admin/competition'],
                            ['label' => 'System Logs', 'route' => 'admin.system-logs.index', 'match' => 'admin/system-logs'],
                        ];
                    @endphp
                    @foreach ($navigation as $item)
                        @php($isActive = request()->is($item['match'], $item['match'].'/*'))
                        <a href="{{ route($item['route']) }}" @class([
                            'w-full whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            'bg-white text-blue-950 shadow-sm' => $isActive,
                            'text-blue-200 hover:bg-blue-900 hover:text-white' => !$isActive,
                        ]) @if ($isActive) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-blue-900 p-4">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 text-xs capitalize text-blue-300">{{ auth()->user()->role }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-blue-200 transition hover:text-white">Sign out</button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 md:h-screen md:overflow-y-auto">
                <header class="ops-topbar sticky top-0 z-30 border-b border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:px-6 md:z-20 md:px-8 md:py-4">
                    <div class="mx-auto flex max-w-7xl items-center gap-3 sm:gap-4">
                        <button type="button" data-open-admin-sidebar class="shrink-0 rounded-lg border border-slate-200 bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600 md:hidden" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            <h1 class="truncate text-lg font-semibold tracking-tight text-white sm:text-xl">{{ $title ?? 'Admin' }}</h1>
                            @isset($subtitle)<p class="mt-0.5 truncate text-xs text-blue-100 sm:mt-1 sm:text-sm">{{ $subtitle }}</p>@endisset
                        </div>
                        <div class="ml-auto hidden max-w-sm shrink-0 text-right lg:block">
                            <p class="truncate text-xs font-semibold uppercase text-amber-100">{{ $themeTitle }}</p>
                            <p class="mt-0.5 truncate text-xs text-blue-100">{{ $themeTagline }}</p>
                        </div>
                    </div>
                </header>
                <div class="ops-shell ops-dashboard mx-auto max-w-7xl px-3 py-5 sm:px-6 sm:py-7 md:px-8 md:py-8">@yield('content')</div>
            </main>
        </div>
        <script>
            const adminSidebar = document.querySelector('[data-admin-sidebar]');
            const adminSidebarBackdrop = document.querySelector('[data-admin-sidebar-backdrop]');
            const adminSidebarOpener = document.querySelector('[data-open-admin-sidebar]');
            const adminSidebarCloser = document.querySelector('[data-close-admin-sidebar]');

            const setAdminSidebarOpen = (open, restoreFocus = true) => {
                if (!adminSidebar || !adminSidebarBackdrop || !adminSidebarOpener) return;

                const desktop = window.innerWidth >= 768;
                const mobileOpen = open && !desktop;
                adminSidebar.classList.toggle('-translate-x-full', !mobileOpen);
                adminSidebarBackdrop.classList.toggle('hidden', !mobileOpen);
                adminSidebarOpener.setAttribute('aria-expanded', String(mobileOpen));
                adminSidebar.toggleAttribute('inert', !desktop && !mobileOpen);
                if (desktop) adminSidebar.removeAttribute('aria-hidden');
                else adminSidebar.setAttribute('aria-hidden', String(!mobileOpen));
                document.body.classList.toggle('overflow-hidden', mobileOpen);

                if (mobileOpen) requestAnimationFrame(() => adminSidebarCloser?.focus());
                else if (restoreFocus && adminSidebar.contains(document.activeElement)) adminSidebarOpener.focus();
            };

            adminSidebarOpener?.addEventListener('click', () => setAdminSidebarOpen(true));
            adminSidebarCloser?.addEventListener('click', () => setAdminSidebarOpen(false));
            adminSidebarBackdrop?.addEventListener('click', () => setAdminSidebarOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setAdminSidebarOpen(false);
            });
            window.addEventListener('resize', () => {
                setAdminSidebarOpen(false, false);
            });
            setAdminSidebarOpen(false, false);

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
        <x-pwa-install />
    </body>
</html>
