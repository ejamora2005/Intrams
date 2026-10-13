import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/js/pwa.js', import.meta.url), 'utf8');

const setup = async ({ secure = true, installed = false, sessionKey = 'login-1', storage = new Map() } = {}) => {
    const buttonListeners = new Map();
    const dismissButtonListeners = new Map();
    const windowListeners = new Map();
    const button = {
        hidden: true,
        disabled: false,
        addEventListener: (name, listener) => buttonListeners.set(name, listener),
        setAttribute() {},
    };
    const dismissButton = {
        addEventListener: (name, listener) => dismissButtonListeners.set(name, listener),
    };
    const status = { hidden: true, textContent: '' };
    const panel = { hidden: true };
    const location = {
        href: secure ? 'https://slsubc.tech/login' : 'http://slsubc.tech/login',
        hostname: 'slsubc.tech',
        assigned: null,
        assign(url) { this.assigned = url; },
    };
    const navigator = {
        userAgent: 'Chrome',
        platform: 'Win32',
        maxTouchPoints: 0,
        getInstalledRelatedApps: async () => installed
            ? [{ platform: 'webapp', url: 'https://slsubc.tech/manifest.webmanifest' }]
            : [],
    };
    const elements = {
        '[data-pwa-install]': panel,
    };
    const groups = {
        '[data-pwa-install-button]': [button],
        '[data-pwa-inline-install-button]': [button],
        '[data-pwa-dismiss]': [dismissButton],
        '[data-pwa-install-status]': [status],
    };
    const window = {
        isSecureContext: secure,
        location,
        navigator,
        localStorage: {
            getItem: key => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, String(value)),
        },
        matchMedia: () => ({ matches: false }),
        addEventListener: (name, listener) => windowListeners.set(name, listener),
    };

    const shell = { dataset: { pwaSessionKey: sessionKey } };

    runInNewContext(source, {
        document: {
            querySelector: selector => selector === '[data-pwa-shell]' ? shell : elements[selector] ?? null,
            querySelectorAll: selector => groups[selector] ?? [],
        },
        window,
        navigator,
        location,
        URL,
        console,
    });
    await new Promise(resolve => setImmediate(resolve));

    return { button, dismissButton, status, panel, location, buttonListeners, dismissButtonListeners, windowListeners };
};

test('install button opens the browser install prompt and hides after installation', async () => {
    const page = await setup();
    let promptCalls = 0;

    page.windowListeners.get('beforeinstallprompt')({
        preventDefault() {},
        prompt: async () => { promptCalls += 1; },
        userChoice: Promise.resolve({ outcome: 'accepted' }),
    });
    await page.buttonListeners.get('click')();

    assert.equal(promptCalls, 1);
    assert.equal(page.button.hidden, true);
    page.windowListeners.get('appinstalled')();
    assert.equal(page.status.hidden, true);
});

test('install button stays hidden when no prompt is offered', async () => {
    const page = await setup();

    assert.equal(page.button.hidden, true);
    await page.buttonListeners.get('click')();

    assert.equal(page.button.hidden, true);
    assert.equal(page.status.hidden, true);
});

test('install button opens HTTPS from an insecure page', async () => {
    const page = await setup({ secure: false });

    assert.equal(page.button.hidden, false);
    await page.buttonListeners.get('click')();

    assert.equal(page.location.assigned, 'https://slsubc.tech/login');
});

test('install button stays hidden when the app is already installed', async () => {
    const page = await setup({ installed: true });

    assert.equal(page.button.hidden, true);
});

test('Later suppresses the install panel for the current login', async () => {
    const storage = new Map();
    const page = await setup({ storage });
    const promptEvent = {
        preventDefault() {},
        prompt: async () => {},
        userChoice: Promise.resolve({ outcome: 'dismissed' }),
    };

    page.windowListeners.get('beforeinstallprompt')(promptEvent);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(page.panel.hidden, false);

    await page.dismissButtonListeners.get('click')();
    assert.equal(page.panel.hidden, true);

    page.windowListeners.get('beforeinstallprompt')(promptEvent);
    assert.equal(page.panel.hidden, true);
});

test('a new login can see the install panel again', async () => {
    const storage = new Map();
    const firstLogin = await setup({ storage, sessionKey: 'login-1' });
    const promptEvent = { preventDefault() {}, prompt: async () => {}, userChoice: Promise.resolve({ outcome: 'dismissed' }) };

    firstLogin.windowListeners.get('beforeinstallprompt')(promptEvent);
    await new Promise(resolve => setImmediate(resolve));
    await firstLogin.dismissButtonListeners.get('click')();

    const nextLogin = await setup({ storage, sessionKey: 'login-2' });
    nextLogin.windowListeners.get('beforeinstallprompt')(promptEvent);
    await new Promise(resolve => setImmediate(resolve));

    assert.equal(nextLogin.panel.hidden, false);
});

test('manifest icons exist outside Apache reserved paths', () => {
    const manifest = JSON.parse(readFileSync(new URL('../../public/manifest.webmanifest', import.meta.url), 'utf8'));

    for (const icon of manifest.icons) {
        assert.match(icon.src, /^\/app-icons\//);
        assert.ok(existsSync(new URL(`../../public${icon.src}`, import.meta.url)), icon.src);
    }
});
