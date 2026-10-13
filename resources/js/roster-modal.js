const modal = document.querySelector('[data-roster-modal]');

if (modal instanceof HTMLDialogElement) {
    const openButtons = [...document.querySelectorAll('[data-open-roster]')];
    const closeButton = modal.querySelector('[data-close-roster]');
    const tabs = [...modal.querySelectorAll('[data-roster-tab]')];
    const panels = [...modal.querySelectorAll('[data-roster-panel]')];
    let lastTrigger = null;

    const selectRoster = (panelId) => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.rosterTarget === panelId;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });

        panels.forEach((panel) => {
            panel.hidden = panel.id !== panelId;
        });
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const panelId = button.dataset.rosterId;
            if (!panelId) return;

            lastTrigger = button;
            selectRoster(panelId);
            if (!modal.open) modal.showModal();
            closeButton?.focus();
        });
    });

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            const panelId = tab.dataset.rosterTarget;
            if (panelId) selectRoster(panelId);
        });

        tab.addEventListener('keydown', (event) => {
            if (!['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].includes(event.key)) return;

            event.preventDefault();
            const direction = event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1;
            const nextIndex = (index + direction + tabs.length) % tabs.length;
            tabs[nextIndex]?.focus();
            tabs[nextIndex]?.click();
        });
    });

    closeButton?.addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
    modal.addEventListener('close', () => lastTrigger?.focus());
}
