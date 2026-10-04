<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Coordinator' }} | INTRAMURALS MANAGEMENT</title>
        <x-pwa-meta />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ops-body min-h-screen bg-blue-950 font-sans text-slate-900 antialiased" style="--ops-background: url('{{ asset(config('landing.background')) }}')">
        @php
            $homeRoute = $homeRoute ?? 'coordinator.dashboard';
            $workspaceLabel = $workspaceLabel ?? 'Coordinator workspace';
            $workspaceRole = auth()->user()?->role;
            $navigation = $navigation ?? match ($workspaceRole) {
                'gam' => [
                    ['label' => 'Dashboard', 'route' => 'gam.dashboard', 'match' => 'gam/dashboard', 'active' => true],
                    ['label' => 'Roster', 'route' => 'gam.dashboard', 'match' => 'gam/dashboard', 'hash' => 'roster-management'],
                    ['label' => 'Medical Certificates', 'route' => 'gam.dashboard', 'match' => 'gam/dashboard', 'hash' => 'medical-certificates'],
                    ['label' => 'Team Standings', 'route' => 'gam.dashboard', 'match' => 'gam/dashboard', 'hash' => 'team-standings'],
                    ['label' => 'Ready Matches', 'route' => 'gam.dashboard', 'match' => 'gam/dashboard', 'hash' => 'ready-matches'],
                    ['label' => 'Rules & Guidelines', 'route' => 'gam.rules.index', 'match' => 'gam/rules-guidelines', 'active' => true],
                ],
                'tabulator' => [
                    ['label' => 'Dashboard', 'route' => 'tabulator.dashboard', 'match' => 'tabulator/dashboard', 'active' => true],
                    ['label' => 'Possible DQ', 'route' => 'tabulator.dashboard', 'match' => 'tabulator/dashboard', 'hash' => 'possible-dq'],
                    ['label' => 'Declare Sports', 'route' => 'tabulator.dashboard', 'match' => 'tabulator/dashboard', 'hash' => 'declare-sports'],
                    ['label' => 'Declared Results', 'route' => 'tabulator.dashboard', 'match' => 'tabulator/dashboard', 'hash' => 'declared-results'],
                    ['label' => 'Match Winners', 'route' => 'tabulator.dashboard', 'match' => 'tabulator/dashboard', 'hash' => 'match-winners'],
                    ['label' => 'Rules & Guidelines', 'route' => 'tabulator.rules.index', 'match' => 'tabulator/rules-guidelines', 'active' => true],
                ],
                default => [
                    ['label' => 'Dashboard', 'route' => 'coordinator.dashboard', 'match' => 'coordinator/dashboard', 'active' => true],
                ],
            };
        @endphp

        <div class="min-h-screen md:flex md:h-screen md:overflow-hidden">
            <div data-ops-sidebar-backdrop class="fixed inset-0 z-40 hidden bg-slate-950/55 backdrop-blur-sm md:hidden" aria-hidden="true"></div>

            <aside id="ops-sidebar" data-ops-sidebar class="ops-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(18rem,calc(100vw-3rem))] -translate-x-full flex-col overflow-hidden bg-blue-950 text-blue-100 shadow-2xl transition-transform duration-200 ease-out md:static md:z-auto md:h-screen md:w-64 md:translate-x-0 md:shadow-none" aria-label="{{ $workspaceLabel }} navigation">
                <div class="flex items-center border-b border-blue-900">
                    <a href="{{ route($homeRoute) }}" aria-label="{{ $workspaceLabel }} dashboard" class="min-w-0 flex-1 px-5 py-5 transition hover:bg-blue-900/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-400">
                        <x-intramurals-brand compact />
                        <span class="mt-3 block truncate text-xs font-semibold uppercase tracking-[0.14em] text-blue-100">{{ $workspaceLabel }}</span>
                    </a>
                    <button type="button" data-close-ops-sidebar class="mr-3 rounded-lg p-2 text-blue-200 transition hover:bg-blue-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 md:hidden" aria-label="Close navigation">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto px-3 py-4" aria-label="{{ $workspaceLabel }} sections">
                    @foreach ($navigation as $item)
                        @php
                            $isActive = ($item['active'] ?? false) && request()->is($item['match'], $item['match'].'/*');
                            $url = route($item['route'], $item['params'] ?? []);
                            if (! empty($item['hash'])) {
                                $url .= '#'.$item['hash'];
                            }
                        @endphp
                        <a href="{{ $url }}" @class([
                            'w-full whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium transition',
                            'bg-white text-blue-950 shadow-sm' => $isActive,
                            'text-blue-200 hover:bg-blue-900 hover:text-white' => ! $isActive,
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
                        <button type="button" data-open-ops-sidebar class="shrink-0 rounded-lg border border-slate-200 bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600 md:hidden" aria-controls="ops-sidebar" aria-expanded="false" aria-label="Open navigation">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                        </button>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-semibold tracking-tight text-white sm:text-xl">{{ $title ?? 'Coordinator' }}</h1>
                            @isset($subtitle)
                                <p class="mt-0.5 truncate text-xs text-blue-100 sm:mt-1 sm:text-sm">{{ $subtitle }}</p>
                            @else
                                <p class="mt-0.5 truncate text-xs text-blue-100 sm:mt-1 sm:text-sm">{{ $workspaceLabel }}</p>
                            @endisset
                        </div>
                    </div>
                </header>
                <div class="ops-shell ops-dashboard mx-auto max-w-7xl px-3 py-5 sm:px-6 sm:py-7 md:px-8 md:py-8">@yield('content')</div>
            </main>
        </div>
        <script>
            const opsSidebar = document.querySelector('[data-ops-sidebar]');
            const opsSidebarBackdrop = document.querySelector('[data-ops-sidebar-backdrop]');
            const opsSidebarOpener = document.querySelector('[data-open-ops-sidebar]');
            const opsSidebarCloser = document.querySelector('[data-close-ops-sidebar]');

            const setOpsSidebarOpen = (open, restoreFocus = true) => {
                if (!opsSidebar || !opsSidebarBackdrop || !opsSidebarOpener) return;

                const desktop = window.innerWidth >= 768;
                const mobileOpen = open && !desktop;
                opsSidebar.classList.toggle('-translate-x-full', !mobileOpen);
                opsSidebarBackdrop.classList.toggle('hidden', !mobileOpen);
                opsSidebarOpener.setAttribute('aria-expanded', String(mobileOpen));
                opsSidebar.toggleAttribute('inert', !desktop && !mobileOpen);
                if (desktop) opsSidebar.removeAttribute('aria-hidden');
                else opsSidebar.setAttribute('aria-hidden', String(!mobileOpen));
                document.body.classList.toggle('overflow-hidden', mobileOpen);

                if (mobileOpen) requestAnimationFrame(() => opsSidebarCloser?.focus());
                else if (restoreFocus && opsSidebar.contains(document.activeElement)) opsSidebarOpener.focus();
            };

            opsSidebarOpener?.addEventListener('click', () => setOpsSidebarOpen(true));
            opsSidebarCloser?.addEventListener('click', () => setOpsSidebarOpen(false));
            opsSidebarBackdrop?.addEventListener('click', () => setOpsSidebarOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpsSidebarOpen(false);
            });
            window.addEventListener('resize', () => {
                setOpsSidebarOpen(false, false);
            });
            setOpsSidebarOpen(false, false);
        </script>
        <x-pwa-install />
    </body>
</html>
