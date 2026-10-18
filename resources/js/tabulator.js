import '../css/tabulator.css';

document.querySelectorAll('[data-tab-filter-list]').forEach(list => {
    const cards = [...list.querySelectorAll('[data-tab-filter-card]')];
    const search = list.querySelector('[data-tab-search]');
    const category = list.querySelector('[data-tab-category]');
    const status = list.querySelector('[data-tab-status]');
    const update = () => {
        const query = (search?.value || '').trim().toLowerCase();
        let count = 0;
        cards.forEach(card => {
            const visible = (!query || card.dataset.search.includes(query))
                && (!category?.value || card.dataset.category === category.value)
                && (!status?.value || card.dataset.status === status.value);
            card.hidden = !visible;
            if (visible) count++;
        });
        const kind = list.dataset.tabCountType || (list.closest('#match-winners') ? 'matches' : 'events');
        list.querySelector('[data-tab-count]').textContent = `${count} of ${cards.length} ${kind}`;
        list.querySelector('[data-tab-filter-empty]').hidden = count > 0 || cards.length === 0;
    };
    [search, category, status].filter(Boolean).forEach(control => control.addEventListener('input', update));
    update();
});

document.querySelectorAll('[data-tab-score-form], [data-tab-match-form]').forEach(form => {
    const modal = document.getElementById(form.dataset.reviewModal);
    const summary = modal.querySelector('[data-tab-review-summary]');
    const error = form.querySelector('[data-tab-score-error]');
    const selects = [...form.querySelectorAll('[data-placement-select]')];
    const rows = () => selects.length
        ? selects.filter(select => select.value).map(select => ({
            label: select.dataset.placementLabel,
            name: select.selectedOptions[0].textContent,
            points: `${Number(select.dataset.placementPoints).toFixed(2)} pts`,
        }))
        : [...form.querySelectorAll('[name="winner_competitor_id"]:checked')].map(input => ({ label: 'Winning competitor', name: input.dataset.competitorName, points: 'Winner' }));
    const refresh = () => {
        const values = rows();
        summary.replaceChildren(...values.map(value => {
            const row = document.createElement('div');
            row.className = 'tab-review-row';
            const label = document.createElement('small'); label.textContent = value.label;
            const name = document.createElement('strong'); name.textContent = value.name;
            const points = document.createElement('span'); points.textContent = value.points;
            row.append(label, name, points);
            return row;
        }));
        const count = form.querySelector('[data-tab-selected-count]');
        if (count) count.textContent = `${values.length} of ${selects.length} placements assigned`;
        error.hidden = true;
    };
    form.addEventListener('change', refresh);
    form.querySelector('[data-tab-review]').addEventListener('click', () => {
        refresh();
        if (!rows().length) {
            error.textContent = selects.length ? 'Choose at least one faction before reviewing placements.' : 'Select the winning competitor before reviewing this match.';
            error.hidden = false;
            (selects[0] || form.querySelector('[name="winner_competitor_id"]'))?.focus();
            return;
        }
        modal.showModal();
        modal.querySelector('input[type=password]').focus();
    });
    modal.addEventListener('close', () => {
        modal.querySelector('input[type=password]').value = '';
        form.querySelector('[data-tab-review]').focus();
    });
    refresh();
});
