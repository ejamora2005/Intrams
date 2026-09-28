<x-guest-layout :livewire="false">
    <main class="relative isolate flex min-h-screen min-h-[100dvh] items-center overflow-hidden bg-slate-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="absolute -left-24 top-10 -z-10 h-72 w-72 rounded-full bg-blue-300/35 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -right-24 bottom-0 -z-10 h-96 w-96 rounded-full bg-blue-500/25 blur-3xl" aria-hidden="true"></div>

        <section class="mx-auto grid w-full max-w-6xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[1.08fr_0.92fr]" aria-labelledby="welcome-heading">
            <div class="bg-blue-950 px-6 py-10 text-white sm:px-10 sm:py-14 lg:px-14 lg:py-20">
                <x-intramurals-brand compact />
                <p class="mt-12 text-xs font-bold uppercase tracking-[0.2em] text-blue-300">SLSU Bontoc Campus</p>
                <h1 id="welcome-heading" class="mt-4 max-w-xl text-3xl font-bold tracking-tight sm:text-5xl">Run every intramurals event from one organized workspace.</h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-blue-100 sm:text-lg">Manage editions, athletes, teams, sports, brackets, schedules, coordinators, and activity records with one shared system.</p>
                <div class="mt-8 flex flex-col gap-3 min-[420px]:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-blue-950 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 focus:ring-offset-blue-950">View schedule and sign in</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-300">Open dashboard</a>
                    @endauth
                </div>
            </div>

            <div class="grid content-center gap-4 bg-slate-50 p-6 sm:grid-cols-2 sm:p-10 lg:grid-cols-1 lg:p-12">
                @foreach ([
                    ['number' => '01', 'title' => 'People and teams', 'description' => 'Maintain reusable courses, athlete records, team rosters, and coordinator access.'],
                    ['number' => '02', 'title' => 'Sports and brackets', 'description' => 'Configure mechanics, register participants, and progress competition results.'],
                    ['number' => '03', 'title' => 'Schedules and records', 'description' => 'Publish the daily board and retain searchable system activity.'],
                ] as $feature)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-300 text-xs font-bold text-blue-950">{{ $feature['number'] }}</span>
                        <h2 class="mt-4 font-bold text-slate-900">{{ $feature['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</x-guest-layout>
