import assert from 'node:assert/strict';
import test from 'node:test';

function browser(status = 'queued') {
    const events = {};
    const timers = new Map();
    let nextTimer = 0;
    const nodes = new Map();
    const node = (selector) => {
        if (!nodes.has(selector)) nodes.set(selector, { textContent: '', hidden: false, value: 0 });
        return nodes.get(selector);
    };
    const machine = { dataset: { state: status }, style: { setProperty: (key, value) => { machine[key] = value; } } };
    const steps = ['queued', 'brewing', 'ready'].map((step) => ({
        dataset: { coffeeStep: step },
        setAttribute(key, value) { this[key] = value; },
        removeAttribute(key) { delete this[key]; },
    }));
    const tracker = {
        dataset: { status, statusUrl: '/demo/orders/test/status', queued: 'Mengantre', brewing: 'Membuat minuman', ready: 'Siap diambil', retry: 'Mencoba kembali', expired: 'Sesi berakhir' },
        querySelector: (selector) => selector === '.coffee-machine' ? machine : node(selector),
        querySelectorAll: () => steps,
    };
    globalThis.document = {
        querySelector: (selector) => selector === '[data-coffee-tracker]' ? tracker : null,
        addEventListener: (name, callback) => { events[name] = callback; },
    };
    globalThis.window = {
        setTimeout: (callback, delay) => { timers.set(++nextTimer, { callback, delay }); return nextTimer; },
        clearTimeout: (id) => { timers.delete(id); },
        addEventListener: (name, callback) => { events[name] = callback; },
    };
    return {
        tracker, machine, steps, node, timers, events,
        async tick() {
            const [id, timer] = timers.entries().next().value;
            timers.delete(id);
            assert.equal(timer.delay, 3000);
            await timer.callback();
        },
    };
}

let moduleVersion = 0;
async function start(ui) {
    await import(`../../resources/js/coffee-order.js?test=${++moduleVersion}`);
    ui.events.DOMContentLoaded();
}

function statusResponse(status, progress) {
    return { ok: true, status: 200, json: async () => ({ status, progress, queue_position: 1 }) };
}

test('polling updates the animation from server progress and stops at ready', async () => {
    const ui = browser();
    const replies = [statusResponse('brewing', 50), statusResponse('ready', 100)];
    globalThis.fetch = async () => replies.shift();
    await start(ui);

    await ui.tick();
    assert.equal(ui.machine.dataset.state, 'brewing');
    assert.equal(ui.machine['--coffee-progress'], 0.5);
    assert.equal(ui.node('progress').value, 50);
    assert.equal(ui.node('[data-coffee-status]').textContent, 'Membuat minuman');
    assert.equal(ui.steps[1]['aria-current'], 'step');

    await ui.tick();
    assert.equal(ui.machine.dataset.state, 'ready');
    assert.equal(ui.node('progress').value, 100);
    assert.equal(ui.node('[data-coffee-ready]').hidden, false);
    assert.equal(ui.timers.size, 0);
});

test('network and invalid responses preserve the confirmed progress and retry', async () => {
    const ui = browser();
    let attempt = 0;
    globalThis.fetch = async () => {
        attempt += 1;
        if (attempt === 1) return statusResponse('brewing', 40);
        if (attempt === 2) throw new TypeError('Network error');
        if (attempt === 3) return { ok: false, status: 503, json: async () => ({}) };
        return statusResponse('ready', 100);
    };
    await start(ui);

    await ui.tick();
    await ui.tick();
    assert.equal(ui.node('progress').value, 40);
    assert.equal(ui.machine.dataset.state, 'brewing');
    assert.equal(ui.node('[data-coffee-error]').hidden, false);
    assert.equal(ui.timers.size, 1);
    await ui.tick();
    assert.equal(ui.node('progress').value, 40);
    assert.equal(ui.timers.size, 1);
    await ui.tick();
    assert.equal(ui.node('[data-coffee-error]').hidden, true);
    assert.equal(ui.node('progress').value, 100);
    assert.equal(ui.timers.size, 0);
});

test('an expired session stops polling without marking the order ready', async () => {
    const ui = browser('brewing');
    globalThis.fetch = async () => ({ ok: false, status: 404, json: async () => ({}) });
    await start(ui);

    await ui.tick();

    assert.equal(ui.tracker.dataset.status, 'brewing');
    assert.equal(ui.node('[data-coffee-error]').textContent, 'Sesi berakhir');
    assert.equal(ui.timers.size, 0);
});

test('a completed order schedules no polling after reload', async () => {
    const ui = browser('ready');

    await start(ui);

    assert.equal(ui.timers.size, 0);
});
