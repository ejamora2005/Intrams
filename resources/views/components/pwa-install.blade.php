<div data-pwa-shell class="pointer-events-none fixed inset-x-0 bottom-0 z-[90] space-y-3 p-3 sm:p-5">
    <div data-pwa-status hidden class="pointer-events-auto mx-auto flex max-w-xl items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-950 shadow-lg shadow-slate-950/20">
        <span data-pwa-status-text>You are offline. Cached assets and the offline page remain available.</span>
        <button type="button" data-pwa-status-dismiss class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-amber-950 transition hover:bg-amber-100">Dismiss</button>
    </div>

    <section data-pwa-install hidden aria-live="polite" class="pointer-events-auto mx-auto max-w-xl overflow-hidden rounded-lg border border-white/15 bg-slate-950 text-white shadow-2xl shadow-slate-950/40">
        <div class="h-1 bg-[linear-gradient(90deg,#0c7345,#d9b87d,#0c7345)]"></div>
        <div class="flex gap-4 p-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold tracking-wide text-blue-900">SLSU</div>
            <div class="min-w-0 flex-1">
                <p data-pwa-install-title class="text-sm font-semibold">Install Intramurals Management</p>
                <p data-pwa-install-copy class="mt-1 text-sm leading-5 text-blue-100">Open the system from your device app list with offline-ready assets.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" data-pwa-install-button class="rounded-lg bg-amber-200 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-100">Install app</button>
                    <button type="button" data-pwa-refresh hidden class="rounded-lg bg-amber-200 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-100">Update now</button>
                    <button type="button" data-pwa-dismiss class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Later</button>
                </div>
            </div>
        </div>
    </section>
</div>
