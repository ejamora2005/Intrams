<div data-pwa-shell class="pointer-events-none fixed inset-x-0 bottom-0 z-[90] space-y-3 p-3 sm:p-5">
    <section data-pwa-install hidden aria-live="polite" class="pointer-events-auto mx-auto max-w-xl overflow-hidden rounded-lg border border-white/15 bg-slate-950 text-white shadow-2xl shadow-slate-950/40">
        <div class="h-1 bg-[linear-gradient(90deg,#0c7345,#d9b87d,#0c7345)]"></div>
        <div class="flex gap-4 p-4">
            <img src="{{ asset(config('landing.appLogo')) }}" alt="" width="64" height="58" class="h-14 w-14 shrink-0 object-contain drop-shadow-[0_8px_18px_rgba(0,0,0,0.45)]" decoding="async">
            <div class="min-w-0 flex-1">
                <p data-pwa-install-title class="text-sm font-semibold">Download INTRAMURAL MS</p>
                <p data-pwa-install-copy class="mt-1 text-sm leading-5 text-blue-100">Open the system from your device app list with offline-ready assets.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" data-pwa-install-button class="rounded-lg bg-amber-200 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-100">Download app</button>
                    <button type="button" data-pwa-refresh hidden class="rounded-lg bg-amber-200 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-100">Update now</button>
                    <button type="button" data-pwa-dismiss class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Later</button>
                </div>
            </div>
        </div>
    </section>
</div>
