const serviceWorkerUrl = '/service-worker.js';
const installPanel = document.querySelector('[data-pwa-install]');
const installButton = document.querySelector('[data-pwa-install-button]');
const dismissButton = document.querySelector('[data-pwa-dismiss]');
const refreshButton = document.querySelector('[data-pwa-refresh]');
const installTitle = document.querySelector('[data-pwa-install-title]');
const installCopy = document.querySelector('[data-pwa-install-copy]');

let installPrompt = null;
let waitingWorker = null;

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const canUseServiceWorker = () => 'serviceWorker' in navigator && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1');

const showInstallPanel = mode => {
    if (!installPanel || (mode !== 'update' && isStandalone())) return;

    installPanel.hidden = false;
    refreshButton?.toggleAttribute('hidden', mode !== 'update');
    installButton?.toggleAttribute('hidden', mode === 'update');

    if (installTitle && installCopy) {
        if (mode === 'update') {
            installTitle.textContent = 'Intramurals update ready';
            installCopy.textContent = 'Refresh once to use the newest app shell.';
        } else {
            installTitle.textContent = 'Install Intramurals Management';
            installCopy.textContent = 'Open the system from your device app list with offline-ready assets.';
        }
    }
};

const hideInstallPanel = () => {
    if (installPanel) installPanel.hidden = true;
};

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    showInstallPanel('install');
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    hideInstallPanel();
});

installButton?.addEventListener('click', async () => {
    if (!installPrompt) return;

    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    hideInstallPanel();
});

dismissButton?.addEventListener('click', hideInstallPanel);

refreshButton?.addEventListener('click', () => {
    if (waitingWorker) {
        waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    } else {
        window.location.reload();
    }
});

if (canUseServiceWorker()) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register(serviceWorkerUrl);

            if (registration.waiting) {
                waitingWorker = registration.waiting;
                showInstallPanel('update');
            }

            registration.addEventListener('updatefound', () => {
                const worker = registration.installing;
                if (!worker) return;

                worker.addEventListener('statechange', () => {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        waitingWorker = worker;
                        showInstallPanel('update');
                    }
                });
            });
        } catch (error) {
            console.warn('PWA registration failed.', error);
        }
    });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        window.location.reload();
    });
}
