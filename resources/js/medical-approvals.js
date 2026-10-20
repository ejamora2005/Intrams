document.querySelectorAll('[data-medical-review-form]').forEach((form) => {
    const entries = [...form.querySelectorAll('input[name="entry_ids[]"]')];
    const count = form.querySelector('[data-medical-selection-count]');
    const refresh = () => {
        const selected = entries.filter((entry) => entry.checked).length;
        entries[0]?.setCustomValidity(selected ? '' : 'Select at least one event to review.');
        if (count) count.textContent = `${selected} of ${entries.length} events selected`;
    };
    entries.forEach((entry) => entry.addEventListener('change', refresh));
    form.querySelectorAll('[data-medical-select]').forEach((button) => {
        button.addEventListener('click', () => {
            entries.forEach((entry) => { entry.checked = button.dataset.medicalSelect === 'all'; });
            refresh();
        });
    });
    refresh();
});
