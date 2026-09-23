<x-guest-layout>
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-5 py-10 sm:px-8">
        <div class="absolute inset-x-0 top-0 h-2 bg-blue-700"></div>
        <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-blue-100/70 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-20 h-80 w-80 rounded-full bg-blue-100/60 blur-3xl"></div>

        <section class="relative w-full max-w-md rounded-2xl border border-blue-100 bg-white p-7 shadow-xl shadow-blue-950/10 sm:p-10" aria-labelledby="login-heading">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-xl bg-blue-700 text-xl font-bold text-white shadow-lg shadow-blue-700/25" aria-hidden="true">SLSU</div>
                <h1 id="login-heading" class="text-2xl font-semibold tracking-tight text-slate-900">INTRAMURALS MANAGEMENT</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Sign in with your administrator or coordinator account.</p>
            </div>

            <x-validation-errors class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" />

            @if (session('status'))
                <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    {{ session('status') }}
                </div>
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

                <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                    Sign in
                </button>
            </form>

            <p class="mt-7 border-t border-slate-100 pt-5 text-center text-xs leading-5 text-slate-500">Access is limited to active administrator and coordinator accounts.</p>
        </section>
    </main>
</x-guest-layout>
