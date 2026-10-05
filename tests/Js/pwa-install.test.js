import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/js/pwa.js', import.meta.url), 'utf8');

const setup = async ({ secure = true, installed = false } = {}) => {
    const buttonListeners = new Map();
    const windowListeners = new Map();
    const button = {
        hidden: true,
        disabled: false,
        addEventListener: (name, listener) => buttonListeners.set(name, listener),
        setAttribute() {},
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
        '[data-pwa-install-status]': [status],
    };
    const window = {
        isSecureContext: secure,
        location,
        navigator,
        matchMedia: () => ({ matches: false }),
        addEventListener: (name, listener) => windowListeners.set(name, listener),
    };

    runInNewContext(source, {
        document: {
            querySelector: selector => elements[selector] ?? null,
            querySelectorAll: selector => groups[selector] ?? [],
        },
        window,
        navigator,
        location,
        URL,
        console,
    });
    await new Promise(resolve => setImmediate(resolve));

    return { button, status, panel, location, buttonListeners, windowListeners };
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

test('install button gives browser instructions when no prompt is offered', async () => {
    const page = await setup();

    assert.equal(page.button.hidden, false);
    await page.buttonListeners.get('click')();

    assert.equal(page.button.hidden, false);
    assert.match(page.status.textContent, /browser menu/);
    assert.equal(page.status.hidden, false);
});

test('install button opens HTTPS from an insecure page', async () => {
    const page = await setup({ secure: false });

    await page.buttonListeners.get('click')();

    assert.equal(page.location.assigned, 'https://slsubc.tech/login');
});

test('install button stays hidden when the app is already installed', async () => {
    const page = await setup({ installed: true });

    assert.equal(page.button.hidden, true);
});
