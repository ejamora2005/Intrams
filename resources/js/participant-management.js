const tabs = [...document.querySelectorAll('[data-team-tab]')];
const panels = [...document.querySelectorAll('[data-team-panel]')];
tabs.forEach((tab) => tab.addEventListener('click', () => {
    tabs.forEach((item) => {
        const active = item === tab;
        item.setAttribute('aria-selected', String(active));
        ['border-blue-700', 'bg-blue-700', 'text-white'].forEach((name) => item.classList.toggle(name, active));
        ['border-slate-300', 'bg-white', 'text-slate-700'].forEach((name) => item.classList.toggle(name, !active));
    });
    panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.teamId !== tab.dataset.teamId));
}));

document.querySelectorAll('[data-participant-remove-form]').forEach((form) => {
    const checkboxes = [...form.querySelectorAll('[name="athlete_entry_ids[]"]')];
    const opener = form.querySelector('[data-participant-remove-open]');
    const count = form.querySelector('[data-participant-selection-count]');
    const error = form.querySelector('[data-participant-selection-error]');
    const modal = form.querySelector('[data-participant-removal-dialog]');
    const search = form.querySelector('[data-team-search]');
    const selectAll = form.querySelector('[data-team-select-all]');
    const rows = [...form.querySelectorAll('[data-team-row]')];
    const checked = () => checkboxes.filter((checkbox) => checkbox.checked);
    const refresh = () => {
        const selection = checked();
        opener.disabled = !selection.length;
        count.textContent = `${selection.length} player${selection.length === 1 ? '' : 's'} selected`;
        error.hidden = true;
        modal.querySelector('[data-participant-removal-names]').replaceChildren(...selection.map((checkbox) => {
            const row = document.createElement('li');
            row.textContent = checkbox.dataset.entryName;
            return row;
        }));
        if (selectAll) {
            const visible = rows.filter((row) => !row.hidden).map((row) => row.querySelector('[name="athlete_entry_ids[]"]'));
            const selected = visible.filter((checkbox) => checkbox.checked).length;
            selectAll.checked = visible.length > 0 && selected === visible.length;
            selectAll.indeterminate = selected > 0 && selected < visible.length;
        }
    };
    form.addEventListener('change', refresh);
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        rows.forEach((row) => { row.hidden = !row.dataset.search.includes(query); });
        form.querySelector('[data-team-empty]').hidden = rows.some((row) => !row.hidden);
        refresh();
    });
    selectAll?.addEventListener('change', () => {
        rows.filter((row) => !row.hidden).forEach((row) => { row.querySelector('[name="athlete_entry_ids[]"]').checked = selectAll.checked; });
        refresh();
    });
    const review = () => {
        refresh();
        if (!checked().length) { error.hidden = false; return; }
        if (!modal.open) opener.click();
    };
    opener.addEventListener('click', refresh);
    modal.querySelector('[data-participant-removal-confirm]').addEventListener('click', (event) => {
        if (!modal.open) { event.preventDefault(); review(); return; }
        form.dataset.removalConfirmed = 'true';
    });
    // Enter in the search field must open review, never remove a player immediately.
    form.addEventListener('submit', (event) => {
        if (form.dataset.removalConfirmed === 'true' && checked().length) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        review();
    }, true);
    modal.addEventListener('close', () => { form.dataset.removalConfirmed = 'false'; });
    window.addEventListener('pageshow', () => {
        form.dataset.removalConfirmed = 'false';
        refresh();
    });
    refresh();
});
