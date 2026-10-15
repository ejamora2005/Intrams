const focusFirstControl = (modal) => {
    const control = modal.querySelector('[autofocus], input:not([type="hidden"]), select, textarea, button');
    control?.focus();
};

const closeModal = (modal) => {
    if (modal instanceof HTMLDialogElement && modal.open) modal.close();
};

document.querySelectorAll('[data-ops-modal]').forEach((modal) => {
    if (!(modal instanceof HTMLDialogElement)) return;

    modal.querySelectorAll('[data-ops-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(modal));
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal(modal);
    });

});

document.querySelectorAll('[data-ops-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.opsModalOpen ?? '');
        if (!(modal instanceof HTMLDialogElement)) return;

        modal.showModal();
        focusFirstControl(modal);
    });
});

const autoModal = document.querySelector('[data-ops-auto-modal]')?.dataset.opsAutoModal;
if (autoModal) {
    const modal = document.getElementById(autoModal);
    if (modal instanceof HTMLDialogElement) {
        modal.showModal();
        focusFirstControl(modal);
    }
}

document.querySelectorAll('[data-ops-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('cursor-wait', 'opacity-70');
            button.dataset.originalLabel ??= button.textContent;
            button.textContent = button.dataset.loadingLabel ?? 'Saving…';
        });
    });
});

document.querySelectorAll('[data-ops-feedback-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[data-ops-feedback]')?.remove());
});
