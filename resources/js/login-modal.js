const modal = document.querySelector('[data-login-modal]');

if (modal) {
    const loginButton = document.querySelector('[data-open-login]');
    const closeButton = modal.querySelector('[data-close-login]');

    const open = () => {
        if (!modal.open) modal.showModal();
    };

    loginButton?.addEventListener('click', open);
    closeButton?.addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        if (event.target === modal) modal.close();
    });
    modal.addEventListener('close', () => loginButton?.focus());

    if (modal.dataset.open === 'true') open();
}
