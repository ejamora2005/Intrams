<x-guest-layout :livewire="false">
    <main class="min-h-screen bg-slate-100 px-4 py-5 sm:px-6 sm:py-8 lg:px-10">
        <section class="mx-auto max-w-7xl overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-xl shadow-slate-900/10" aria-labelledby="schedule-heading">
            <header class="flex flex-col gap-4 bg-blue-950 px-5 py-5 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <x-intramurals-brand compact />
                <button type="button" data-open-login class="w-fit rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-blue-900 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-950">Login</button>
            </header>

            <div class="px-5 py-6 sm:px-8 sm:py-8">
                <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current event</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $currentEdition?->name ?? 'No active intramurals edition' }}</p>
                    </div>
                    <div class="lg:text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Happening today</p>
                        <h1 id="schedule-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $todayLabel }}</h1>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-300">
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
                <p id="schedule-refresh-note" class="mt-3 text-right text-xs text-slate-500">Schedule snapshot · Refresh this page manually to load updated data.</p>
            </div>
        </section>
    </main>

    <dialog data-login-modal data-open="{{ $errors->any() || session('status') ? 'true' : 'false' }}" aria-labelledby="login-heading" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-blue-100 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45 backdrop:backdrop-blur-md">
        <section class="relative p-7 sm:p-9">
            <button type="button" data-close-login aria-label="Close login" class="absolute right-4 top-4 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600">✕</button>
            <div class="mb-7 text-center">
                <x-intramurals-brand heading-id="login-heading" />
                <p class="mt-2 text-sm leading-6 text-slate-600">Sign in with your administrator or coordinator account.</p>
            </div>

            <x-validation-errors class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" />

            @if (session('status'))
                <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <x-label for="email" value="Email address" class="text-sm font-medium text-slate-700" />
                    <x-input id="email" class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@school.edu" />
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <x-label for="password" value="Password" class="text-sm font-medium text-slate-700" />
                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-blue-700 transition hover:text-blue-900 focus:outline-none focus:underline" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>
                    <x-input id="password" class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-slate-900 shadow-sm transition focus:border-blue-600 focus:ring-blue-600" type="password" name="password" required autocomplete="current-password" />
                </div>
                <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Sign in</button>
            </form>
            <p class="mt-7 border-t border-slate-100 pt-5 text-center text-xs leading-5 text-slate-500">Access is limited to active administrator and coordinator accounts.</p>
        </section>
    </dialog>
</x-guest-layout>
