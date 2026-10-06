import './bootstrap';
import './pwa';

if (document.querySelector('[data-score-sheet-export]')) {
    void import('./score-sheet-export');
}

if (document.querySelector('[data-volleyball-score-sheet]')) {
    void import('./volleyball-score-sheet');
}

if (document.querySelector('[data-login-modal]')) {
    void import('./login-modal');
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
