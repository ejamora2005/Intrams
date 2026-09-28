<x-guest-layout :livewire="false">
    <main class="min-h-screen min-h-[100dvh] bg-slate-100 px-2 py-3 min-[380px]:px-3 sm:px-6 sm:py-8 lg:px-10">
        <section class="mx-auto max-w-7xl overflow-hidden rounded-xl border border-slate-300 bg-white shadow-xl shadow-slate-900/10 sm:rounded-2xl" aria-labelledby="schedule-heading">
            <header class="flex items-center justify-between gap-3 bg-blue-950 px-4 py-4 text-white sm:px-8 sm:py-5">
                <x-intramurals-brand compact class="min-w-0" />
                <button type="button" data-open-login class="shrink-0 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-blue-900 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-950 sm:px-5">Login</button>
            </header>

            <div class="px-4 py-5 sm:px-8 sm:py-8">
                <div class="mb-5 flex flex-col gap-3 sm:mb-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current event</p>
                        <p class="mt-1 break-words text-base font-semibold text-slate-900 sm:text-lg">{{ $currentEdition?->name ?? 'No active intramurals edition' }}</p>
                    </div>
                    <div class="lg:text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Happening today</p>
                        <h1 id="schedule-heading" class="mt-1 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $todayLabel }}</h1>
                    </div>
                </div>

                <div class="hidden overflow-x-auto rounded-xl border border-slate-300 lg:block">
                    <table class="w-full min-w-[760px] table-fixed border-collapse text-left" aria-describedby="schedule-refresh-note">
                        <caption class="sr-only">Competitions scheduled for {{ $todayLabel }}</caption>
                        <colgroup>
                            <col class="w-[20%]">
                            <col class="w-[18%]">
                            <col class="w-[40%]">
                            <col class="w-[22%]">
                        </colgroup>
                        <thead class="bg-blue-50 text-xs font-bold uppercase tracking-wide text-blue-950">
                            <tr>
                                <th scope="col" class="border-b border-r border-slate-300 px-5 py-4">Sports</th>
                                <th scope="col" class="border-b border-r border-slate-300 px-5 py-4">Time of day</th>
                                <th scope="col" class="border-b border-r border-slate-300 px-5 py-4 text-center">Current team playing</th>
                                <th scope="col" class="border-b border-slate-300 px-5 py-4">Facilitator</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white text-sm text-slate-800">
                            @forelse ($todaySchedules->groupBy('sport') as $sport => $sportSchedules)
                                @php
                                    $sharedFacilitator = $sportSchedules->pluck('facilitator')->unique()->count() === 1;
                                @endphp
                                @foreach ($sportSchedules as $schedule)
                                <tr>
                                    @if ($loop->first)
                                        <th scope="rowgroup" rowspan="{{ $sportSchedules->count() }}" class="border-b border-r border-slate-300 px-5 py-5 text-left align-middle font-semibold text-slate-950">{{ $sport }}</th>
                                    @endif
                                    <td class="border-b border-r border-slate-300 px-5 py-5">{{ $schedule['period'] }}</td>
                                    <td class="border-b border-r border-slate-300 px-5 py-5 text-center font-semibold uppercase tracking-wide text-slate-950">
                                        @if ($schedule['game'])
                                            <span class="mb-1 block text-xs font-bold tracking-[0.14em] text-blue-700">{{ $schedule['game'] }}</span>
                                        @endif
                                        <span class="block">{{ $schedule['competitors'] }}</span>
                                    </td>
                                    @if ($sharedFacilitator)
                                        @if ($loop->first)
                                            <td rowspan="{{ $sportSchedules->count() }}" class="border-b border-slate-300 px-5 py-5 align-middle">{{ $schedule['facilitator'] }}</td>
                                        @endif
                                    @else
                                        <td class="border-b border-slate-300 px-5 py-5">{{ $schedule['facilitator'] }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-14 text-center text-sm text-slate-500">No competitions are scheduled for today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-3 lg:hidden" aria-describedby="schedule-refresh-note">
                    @forelse ($todaySchedules as $schedule)
                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-blue-100 bg-blue-50 px-4 py-3">
                                <h2 class="min-w-0 truncate text-sm font-bold text-blue-950">{{ $schedule['sport'] }}</h2>
                                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-blue-800 shadow-sm">{{ $schedule['period'] }}</span>
                            </div>
                            <dl class="space-y-3 px-4 py-4 text-sm">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current team playing</dt>
                                    <dd class="mt-1 break-words font-semibold uppercase leading-6 text-slate-950">
                                        @if ($schedule['game'])
                                            <span class="mr-1 text-xs font-bold tracking-[0.12em] text-blue-700">{{ $schedule['game'] }}</span>
                                        @endif
                                        {{ $schedule['competitors'] }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Facilitator</dt>
                                    <dd class="mt-1 break-words text-slate-800">{{ $schedule['facilitator'] }}</dd>
                                </div>
                            </dl>
                        </article>
                    @empty
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">No competitions are scheduled for today.</div>
                    @endforelse
                </div>
                <p id="schedule-refresh-note" class="mt-3 text-right text-xs text-slate-500">Schedule snapshot · Refresh this page manually to load updated data.</p>
            </div>
        </section>
    </main>

    <dialog data-login-modal data-open="{{ $errors->any() || session('status') ? 'true' : 'false' }}" aria-labelledby="login-heading" class="m-auto max-h-[calc(100dvh-1rem)] w-[calc(100%-1rem)] max-w-md overflow-y-auto overscroll-contain rounded-xl border border-blue-100 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45 backdrop:backdrop-blur-md sm:max-h-[calc(100dvh-2rem)] sm:w-[calc(100%-2rem)] sm:rounded-2xl">
        <section class="relative p-5 min-[380px]:p-6 sm:p-9">
            <button type="button" data-close-login aria-label="Close login" class="absolute right-4 top-4 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600">✕</button>
            <div class="mb-5 pr-8 text-center sm:mb-7 sm:pr-0">
                <x-intramurals-brand heading-id="login-heading" />
                <p class="mt-2 text-sm leading-6 text-slate-600">Sign in with your administrator or coordinator account.</p>
            </div>

            <x-validation-errors class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" />

            @if (session('status'))
                <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5">
                @csrf
                <div>
                    <x-label for="email" value="Email address" class="text-sm font-medium text-slate-700" />
                    <x-input id="email" class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-base text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600 sm:text-sm" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@school.edu" />
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <x-label for="password" value="Password" class="text-sm font-medium text-slate-700" />
                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-blue-700 transition hover:text-blue-900 focus:outline-none focus:underline" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>
                    <x-input id="password" class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-base text-slate-900 shadow-sm transition focus:border-blue-600 focus:ring-blue-600 sm:text-sm" type="password" name="password" required autocomplete="current-password" />
                </div>
                <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Sign in</button>
            </form>
            <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs leading-5 text-slate-500 sm:mt-7 sm:pt-5">Access is limited to active administrator and coordinator accounts.</p>
        </section>
    </dialog>
</x-guest-layout>
