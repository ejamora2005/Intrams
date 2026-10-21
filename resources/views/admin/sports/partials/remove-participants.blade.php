<p data-participant-selection-count aria-live="polite" class="text-xs text-slate-500">0 players selected</p>
<p data-participant-selection-error hidden role="alert" class="mt-2 text-sm font-semibold text-red-700">Select at least one player to remove.</p>
<dialog id="{{ $removalDialogId }}" data-ops-modal data-participant-removal-dialog class="w-[min(30rem,calc(100vw-2rem))] rounded-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60" aria-labelledby="{{ $removalDialogId }}-heading">
    <div class="p-5">
        <h3 id="{{ $removalDialogId }}-heading" class="text-lg font-semibold">Remove selected players?</h3>
        <p class="mt-3 text-sm text-slate-500">Only these registrations will be withdrawn from this sport. Student records, schedules, brackets and declared results are kept.</p>
        <ul data-participant-removal-names class="mt-4 max-h-64 list-inside list-disc space-y-2 overflow-y-auto text-sm"></ul>
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button>
            <button type="submit" data-participant-removal-confirm data-loading-label="Removing..." class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Remove registrations</button>
        </div>
    </div>
</dialog>
