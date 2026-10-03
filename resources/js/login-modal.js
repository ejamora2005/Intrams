const modal = document.querySelector('[data-login-modal]');

if (modal) {
    const loginButton = document.querySelector('[data-open-login]');
    const closeButton = modal.querySelector('[data-close-login]');
    const passwordToggles = modal.querySelectorAll('[data-toggle-password]');

    const open = () => {
        if (!modal.open) modal.showModal();
    };

    loginButton?.addEventListener('click', open);
    closeButton?.addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        if (event.target === modal) modal.close();
    });
    modal.addEventListener('close', () => loginButton?.focus());
    passwordToggles.forEach(toggle => {
        const targetId = toggle.dataset.passwordTarget;
        const input = targetId ? modal.querySelector(`#${CSS.escape(targetId)}`) : null;
        const openIcon = toggle.querySelector('[data-eye-open]');
        const closedIcon = toggle.querySelector('[data-eye-closed]');

        toggle.addEventListener('click', () => {
            if (!(input instanceof HTMLInputElement)) return;

            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!visible));
            toggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            openIcon?.classList.toggle('hidden', !visible);
            closedIcon?.classList.toggle('hidden', visible);
            input.focus();
        });
    });

    if (modal.dataset.open === 'true') open();
}
