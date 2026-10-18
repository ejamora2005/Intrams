<dialog data-delete-schedule-dialog aria-labelledby="delete-schedule-title" aria-describedby="delete-schedule-description" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/70">
    <form method="POST" data-delete-schedule-form class="p-6">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirmation" value="DELETE">
        <input type="hidden" name="return_to" value="competition">
        <h2 id="delete-schedule-title" class="text-xl font-semibold text-slate-900">Delete this schedule?</h2>
        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p data-delete-schedule-name class="font-semibold text-slate-900"></p>
            <p data-delete-schedule-time class="mt-1 text-sm text-slate-600"></p>
        </div>
        <p id="delete-schedule-description" class="mt-4 text-sm leading-6 text-slate-600">This removes the schedule and its participant list from Live Competition and the public schedule. The bracket lineup, player registrations, and recorded wins are kept.</p>
        <p class="mt-2 text-sm font-medium" style="color: #b91c1c">This schedule deletion cannot be undone.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-cancel-delete-schedule autofocus class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="submit" data-confirm-delete-schedule class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50">Delete schedule</button>
        </div>
    </form>
</dialog>
<script>
    (() => {
        const dialog = document.querySelector('[data-delete-schedule-dialog]');
        const form = dialog.querySelector('[data-delete-schedule-form]');
        const confirmButton = dialog.querySelector('[data-confirm-delete-schedule]');
        let opener = null;
        let submitting = false;

        document.querySelectorAll('[data-delete-schedule]').forEach((button) => button.addEventListener('click', () => {
            opener = button;
            form.action = button.dataset.deleteUrl;
            form.elements.return_to.value = button.dataset.deleteReturnTo;
            dialog.querySelector('[data-delete-schedule-name]').textContent = button.dataset.deleteLabel;
            dialog.querySelector('[data-delete-schedule-time]').textContent = button.dataset.deleteWhen;
            submitting = false;
            confirmButton.disabled = false;
            confirmButton.textContent = 'Delete schedule';
            dialog.showModal();
        }));
        dialog.querySelector('[data-cancel-delete-schedule]').addEventListener('click', () => {
            if (!submitting) dialog.close();
        });
        dialog.addEventListener('cancel', (event) => {
            if (submitting) event.preventDefault();
        });
        dialog.addEventListener('close', () => opener?.focus());
        form.addEventListener('submit', (event) => {
            if (submitting) return event.preventDefault();
            submitting = true;
            confirmButton.disabled = true;
            confirmButton.textContent = 'Deleting…';
        });
    })();
</script>
