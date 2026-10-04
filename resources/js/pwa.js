const serviceWorkerUrl = '/service-worker.js';
const installPanel = document.querySelector('[data-pwa-install]');
const installButtons = Array.from(document.querySelectorAll('[data-pwa-install-button]'));
const dismissButtons = Array.from(document.querySelectorAll('[data-pwa-dismiss]'));
const refreshButtons = Array.from(document.querySelectorAll('[data-pwa-refresh]'));
const installTitle = document.querySelector('[data-pwa-install-title]');
const installCopy = document.querySelector('[data-pwa-install-copy]');
const installStatuses = Array.from(document.querySelectorAll('[data-pwa-install-status]'));

let installPrompt = null;
let waitingWorker = null;
const installUnavailableMessage = 'Chrome cannot open the install prompt right now. If the address bar shows Open in app, SLSU INTRAMURALS is already installed on this device. Remove the installed app first if you want the download button to appear again.';

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const canUseServiceWorker = () => 'serviceWorker' in navigator && (window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1');

const setInstallButtonsVisible = visible => {
    installButtons.forEach(button => {
        button.hidden = !visible;
    });
};

const setRefreshButtonsVisible = visible => {
    refreshButtons.forEach(button => {
        button.hidden = !visible;
    });
};

const setInstallStatus = message => {
    installStatuses.forEach(status => {
        status.textContent = message || '';
        status.hidden = !message;
    });
};

const relatedAppInstalled = async () => {
    if (!('getInstalledRelatedApps' in navigator)) return false;

    try {
        const relatedApps = await navigator.getInstalledRelatedApps();
        return relatedApps.some(app => app.platform === 'webapp' || app.url?.includes('manifest.webmanifest'));
    } catch {
        return false;
    }
};

const appIsInstalled = async () => isStandalone() || await relatedAppInstalled();

const showInstallPanel = async mode => {
    if (mode !== 'update' && await appIsInstalled()) {
        hideInstallControls();
        return;
    }

    if (installPanel) installPanel.hidden = false;

    const updateMode = mode === 'update';
    setInstallStatus('');
    setRefreshButtonsVisible(updateMode);
    setInstallButtonsVisible(!updateMode && Boolean(installPrompt));

    if (installTitle && installCopy) {
        if (updateMode) {
            installTitle.textContent = 'SLSU INTRAMURALS update ready';
            installCopy.textContent = 'Refresh once to use the newest app shell.';
        } else {
            installTitle.textContent = 'Download SLSU INTRAMURALS App';
            installCopy.textContent = 'Open the system from your device app list with offline-ready assets.';
        }
    }
};

const hideInstallControls = () => {
    if (installPanel) installPanel.hidden = true;
    setInstallStatus('');
    setInstallButtonsVisible(false);
    setRefreshButtonsVisible(false);
};

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    void showInstallPanel('install');
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    hideInstallControls();
});

installButtons.forEach(button => {
    button.addEventListener('click', async () => {
        if (!installPrompt) {
            setInstallStatus(installUnavailableMessage);
            return;
        }

        const prompt = installPrompt;
        installPrompt = null;

        try {
            prompt.prompt();
            const choice = await prompt.userChoice;

            if (choice.outcome === 'accepted') {
                setInstallStatus('Installing SLSU INTRAMURALS App...');
            } else {
                setInstallStatus('Install was dismissed. Refresh this page if Chrome offers the install prompt again.');
            }
        } catch {
            setInstallStatus(installUnavailableMessage);
        } finally {
            setInstallButtonsVisible(false);
        }
    });
});

dismissButtons.forEach(button => {
    button.addEventListener('click', hideInstallControls);
});

refreshButtons.forEach(button => {
    button.addEventListener('click', () => {
        if (waitingWorker) {
            waitingWorker.postMessage({ type: 'SKIP_WAITING' });
        } else {
            window.location.reload();
        }
    });
});

if (canUseServiceWorker()) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register(serviceWorkerUrl);

            if (registration.waiting) {
                waitingWorker = registration.waiting;
                void showInstallPanel('update');
            }

            registration.addEventListener('updatefound', () => {
                const worker = registration.installing;
                if (!worker) return;

                worker.addEventListener('statechange', () => {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        waitingWorker = worker;
                        void showInstallPanel('update');
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

if (isStandalone()) {
    hideInstallControls();
}
