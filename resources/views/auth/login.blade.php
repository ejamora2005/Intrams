<x-guest-layout :livewire="false">
    @include('auth.partials.public-schedule')

    @php
        $themeTitle = config('intramurals.theme.title');
        $themeTagline = config('intramurals.theme.tagline');
    @endphp

    <dialog data-login-modal data-open="{{ $errors->any() || session('status') ? 'true' : 'false' }}" aria-labelledby="login-heading" class="m-auto max-h-[calc(100dvh-1rem)] w-[calc(100%-1rem)] max-w-4xl overflow-y-auto overscroll-contain rounded-2xl border border-white/70 bg-white p-0 shadow-2xl shadow-slate-950/30 backdrop:bg-slate-950/45 backdrop:backdrop-blur-md sm:max-h-[calc(100dvh-2rem)] sm:w-[calc(100%-2rem)]">
        <section class="relative grid overflow-hidden md:min-h-[34rem] md:grid-cols-[0.92fr_1.08fr]">
            <button type="button" data-close-login aria-label="Close login" class="absolute right-4 top-4 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/90 text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-white hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-blue-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>

            <aside class="relative hidden flex-col justify-between overflow-hidden bg-blue-950 p-8 text-white md:flex">
                <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#7AAACE,#F59E0B,#34D399)]" aria-hidden="true"></div>
                <div>
                    <x-intramurals-brand compact class="text-white" />
                    <div class="mt-10">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-blue-200">Operations portal</p>
                        <h2 class="mt-3 text-3xl font-semibold leading-tight tracking-tight">{{ $themeTitle }}</h2>
                        <p class="mt-4 text-sm font-semibold leading-6 text-amber-100">{{ $themeTagline }}</p>
                        <p class="mt-4 text-sm leading-6 text-blue-100">Manage standings, rosters, events, and live operations from a protected workspace.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
                        <span class="rounded-lg border border-white/15 bg-white/10 px-3 py-2 text-blue-50">Admin</span>
                        <span class="rounded-lg border border-white/15 bg-white/10 px-3 py-2 text-blue-50">GAM</span>
                        <span class="rounded-lg border border-white/15 bg-white/10 px-3 py-2 text-blue-50">Tabulator</span>
                        <span class="rounded-lg border border-white/15 bg-white/10 px-3 py-2 text-blue-50">Coordinator</span>
                    </div>
                    <button type="button" data-pwa-install-button hidden class="flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200/70 bg-amber-200 px-4 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-slate-950/15 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 focus:ring-offset-blue-950">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v11m0 0 4-4m-4 4-4-4M5 17v1.5A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5V17" /></svg>
                        <span>Download SLSU INTRAMURALS App</span>
                    </button>
                    <div class="rounded-xl border border-white/15 bg-white/10 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Today</p>
                        <p class="mt-2 text-sm leading-6 text-blue-50">Use your assigned account only. Password confirmation is required for protected score changes.</p>
                    </div>
                </div>
            </aside>

            <div class="p-5 min-[380px]:p-6 sm:p-9 md:p-10">
                <div class="mb-7 pr-10">
                    <div class="md:hidden">
                        <x-intramurals-brand />
                    </div>
                    <p class="mt-6 hidden text-sm font-semibold uppercase tracking-[0.16em] text-blue-700 md:block">Welcome back</p>
                    <h1 id="login-heading" class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">Sign in to continue</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $themeTagline }} Use your administrator, GAM, Tabulator, or coordinator account.</p>
                </div>

                <x-validation-errors class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" />

                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <x-label for="email" value="Email address" class="text-sm font-semibold text-slate-700" />
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z" /><path d="m5.5 7 5.35 4.28a2 2 0 0 0 2.3 0L18.5 7" /></svg>
                            <x-input id="email" class="block w-full rounded-xl border-slate-300 bg-slate-50/80 px-3 py-3 pl-11 text-base text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:bg-white focus:border-blue-600 focus:bg-white focus:ring-blue-600 sm:text-sm" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@school.edu" />
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <x-label for="password" value="Password" class="text-sm font-semibold text-slate-700" />
                            @if (Route::has('password.request'))
                                <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-900 focus:outline-none focus:underline" href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7.75 10V8.25a4.25 4.25 0 0 1 8.5 0V10" /><path d="M6.75 10h10.5A1.75 1.75 0 0 1 19 11.75v6.5A1.75 1.75 0 0 1 17.25 20H6.75A1.75 1.75 0 0 1 5 18.25v-6.5A1.75 1.75 0 0 1 6.75 10Z" /></svg>
                            <x-input id="password" class="block w-full rounded-xl border-slate-300 bg-slate-50/80 px-3 py-3 pl-11 pr-12 text-base text-slate-900 shadow-sm transition hover:bg-white focus:border-blue-600 focus:bg-white focus:ring-blue-600 sm:text-sm" type="password" name="password" required autocomplete="current-password" />
                            <button type="button" data-toggle-password data-password-target="password" aria-label="Show password" aria-pressed="false" class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600">
                                <svg data-eye-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.75 12s3.5-6.25 9.25-6.25S21.25 12 21.25 12 17.75 18.25 12 18.25 2.75 12 2.75 12Z" /><path d="M14.25 12a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                                <svg data-eye-closed class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3.75 3.75 20.25 20.25" /><path d="M10.58 10.58a2.25 2.25 0 0 0 2.84 2.84" /><path d="M7.13 7.41C4.38 9.12 2.75 12 2.75 12s3.5 6.25 9.25 6.25c1.64 0 3.08-.51 4.31-1.22" /><path d="M19.04 15.12c1.43-1.45 2.21-3.12 2.21-3.12S17.75 5.75 12 5.75c-.73 0-1.42.1-2.07.27" /></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="remember_me" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                            <input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600">
                            <span>Keep me signed in</span>
                        </label>
                    </div>
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                        <span>Sign in</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14m-5-5 5 5-5 5" /></svg>
                    </button>
                </form>
                <p class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-medium leading-5 text-slate-600">Access is limited to active intramurals operations accounts.</p>
            </div>
        </section>
    </dialog>
</x-guest-layout>
