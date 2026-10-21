import '../css/public-event-results.css';

const dialog = document.querySelector('[data-public-event-results]');

if (dialog) {
    let opener = null;
    const open = () => {
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('[data-public-results-search]').focus({ preventScroll: true });
    };
    document.querySelectorAll('[data-open-event-results]').forEach(trigger => {
        trigger.addEventListener('click', event => {
            event.preventDefault();
            opener = trigger;
            history.replaceState(null, '', '#event-results');
            open();
        });
    });
    const syncHash = () => {
        if (location.hash === '#event-results') open();
        else if (dialog.open) dialog.close();
    };
    window.addEventListener('hashchange', syncHash);
    dialog.addEventListener('close', () => {
        if (location.hash === '#event-results') history.replaceState(null, '', location.pathname + location.search);
        opener?.focus();
    });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const box = dialog.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
    });

    const cards = [...dialog.querySelectorAll('[data-public-result]')];
    const search = dialog.querySelector('[data-public-results-search]');
    const category = dialog.querySelector('[data-public-results-category]');
    const count = dialog.querySelector('[data-public-results-count]');
    const empty = dialog.querySelector('[data-public-results-empty]');
    const groups = [...dialog.querySelectorAll('[data-public-result-group]')];
    const filter = () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        cards.forEach(card => {
            card.hidden = !((!query || card.dataset.search.includes(query)) && (!category.value || card.dataset.category === category.value));
            if (!card.hidden) visible++;
        });
        groups.forEach(group => {
            group.hidden = !group.querySelector('[data-public-result]:not([hidden])');
        });
        count.textContent = `${visible} of ${cards.length} declared events`;
        if (empty) empty.hidden = visible > 0;
    };
    search.addEventListener('input', filter);
    category.addEventListener('change', filter);
    filter();
    syncHash();
}
