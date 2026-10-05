const serviceWorkerUrl = '/service-worker.js';
const installPanel = document.querySelector('[data-pwa-install]');
const installButtons = Array.from(document.querySelectorAll('[data-pwa-install-button]'));
const inlineInstallButtons = Array.from(document.querySelectorAll('[data-pwa-inline-install-button]'));
const dismissButtons = Array.from(document.querySelectorAll('[data-pwa-dismiss]'));
const refreshButtons = Array.from(document.querySelectorAll('[data-pwa-refresh]'));
const installTitle = document.querySelector('[data-pwa-install-title]');
const installCopy = document.querySelector('[data-pwa-install-copy]');
const installStatuses = Array.from(document.querySelectorAll('[data-pwa-install-status]'));

let installPrompt = null;
let waitingWorker = null;
let installPromptInProgress = false;

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isLocalhost = () => ['localhost', '127.0.0.1'].includes(location.hostname);
const canUseServiceWorker = () => 'serviceWorker' in navigator && (window.isSecureContext || isLocalhost());
const needsSecureInstallContext = () => !window.isSecureContext && !isLocalhost();
const secureInstallUrl = () => {
    const url = new URL(window.location.href);
    url.protocol = 'https:';

    return url.toString();
};

const setInstallButtonsVisible = visible => {
    installButtons.forEach(button => {
        button.hidden = !visible;
    });
};

const setInlineInstallButtonsVisible = visible => {
    inlineInstallButtons.forEach(button => {
        button.hidden = !visible;
    });
};

const setInstallButtonsDisabled = disabled => {
    installButtons.forEach(button => {
        button.disabled = disabled;
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
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

const showInlineInstallAction = async () => {
    if (await appIsInstalled()) {
        hideInstallControls();
        return;
    }

    setInlineInstallButtonsVisible(Boolean(installPrompt) || needsSecureInstallContext());
};

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    void showInlineInstallAction();
    void showInstallPanel('install');
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    hideInstallControls();
});

installButtons.forEach(button => {
    button.addEventListener('click', async () => {
        if (installPromptInProgress) return;

        if (!installPrompt) {
            if (needsSecureInstallContext()) {
                setInstallStatus('Opening the secure SLSU INTRAMURALS page so Chrome can download the app...');
                window.location.assign(secureInstallUrl());
                return;
            }

            hideInstallControls();
            return;
        }

        const prompt = installPrompt;
        installPrompt = null;
        installPromptInProgress = true;
        setInstallButtonsDisabled(true);

        let accepted = false;

        try {
            await prompt.prompt();
            const choice = await prompt.userChoice;
            accepted = choice.outcome === 'accepted';

            if (accepted) {
                setInstallStatus('Installing SLSU INTRAMURALS App...');
            }
        } catch (error) {
            console.warn('PWA install prompt failed.', error);
        } finally {
            installPromptInProgress = false;
            setInstallButtonsDisabled(false);

            if (accepted) {
                setInstallButtonsVisible(false);
            } else {
                hideInstallControls();
            }
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
} else {
    void showInlineInstallAction();
}
