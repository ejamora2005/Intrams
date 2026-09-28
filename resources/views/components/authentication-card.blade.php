<div class="flex min-h-screen min-h-[100dvh] flex-col items-center bg-slate-50 px-4 py-8 sm:justify-center sm:px-6">
    <div>
        {{ $logo }}
    </div>

    <div class="mt-6 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-6 shadow-xl shadow-slate-900/10 sm:max-w-md sm:px-7 sm:py-8">
        {{ $slot }}
    </div>
</div>
