import './bootstrap';
import './public-programme-editor';
import './pwa';
import './ops-interactions';
import './competition-schedule';
import './medical-approvals';
import './cultural-events';
import './participant-management';

if (document.querySelector('[data-tabulator-workspace]')) {
    void import('./tabulator');
}

// Shared in-page search for long GAM, Tabulator, and Admin lists. Each search
// is scoped to its nearest container, so separate panels never affect one
// another's rows or result count.
const listSearchNormalize = (value) => (value || '').toString().trim().toLowerCase();

document.querySelectorAll('[data-list-search-container]').forEach((container) => {
    const input = container.querySelector('[data-list-search-input]');
    if (!input) return;

    const rows = [...container.querySelectorAll('[data-list-search-row]')]
        .filter((row) => row.closest('[data-list-search-container]') === container);
    const empty = [...container.querySelectorAll('[data-list-search-empty]')]
        .find((item) => item.closest('[data-list-search-container]') === container);
    const count = container.querySelector('[data-list-search-count]');
    const update = () => {
        const query = listSearchNormalize(input.value);
        let visible = 0;

        rows.forEach((row) => {
            const matches = !row.hidden && (!query || listSearchNormalize(row.dataset.searchText || row.textContent).includes(query));
            row.classList.toggle('hidden', !matches);
            if (matches) visible += 1;
        });

        if (empty) empty.hidden = rows.length === 0 || visible > 0;
        if (count) count.textContent = query ? `${visible} ${visible === 1 ? 'match' : 'matches'}` : `${rows.length} ${rows.length === 1 ? 'item' : 'items'}`;
    };

    input.addEventListener('input', update);
    container.addEventListener('list-search:refresh', update);
    update();
});

document.querySelectorAll('[data-select-search]').forEach((input) => {
    const select = document.getElementById(input.dataset.selectSearch);
    if (!select) return;

    input.addEventListener('input', () => {
        const query = listSearchNormalize(input.value);
        [...select.options].forEach((option, index) => {
            option.hidden = index > 0 && query !== '' && !listSearchNormalize(option.textContent).includes(query);
        });
    });
});

if (document.querySelector('[data-score-sheet-export]')) {
    void import('./score-sheet-export');
}

if (document.querySelector('[data-volleyball-score-sheet]')) {
    void import('./volleyball-score-sheet');
}

if (document.querySelector('[data-sport-score-sheet]')) {
    void import('./sport-score-sheets');
}

if (document.querySelector('[data-login-modal]')) {
    void import('./login-modal');
}

if (document.querySelector('[data-roster-modal]')) {
    void import('./roster-modal');
}

document.querySelectorAll('[data-declare-winners]').forEach(form => {
    const selects = [...form.querySelectorAll('[data-placement-select]')];

    if (form.dataset.allowRepeatedWinners === 'true') {
        return;
    }

    const syncPlacementOptions = () => {
        const selectedValues = new Set(
            selects
                .map(select => select.value)
                .filter(Boolean),
        );

        selects.forEach(select => {
            [...select.options].forEach(option => {
                option.disabled = Boolean(option.value) && option.value !== select.value && selectedValues.has(option.value);
            });
        });
    };

    selects.forEach(select => select.addEventListener('change', syncPlacementOptions));
    syncPlacementOptions();
});
