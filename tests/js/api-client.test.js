import assert from 'node:assert/strict';
import test from 'node:test';

import { getJson, postJson } from '../../resources/js/shared/api.js';

function stubBrowser(fetchImpl) {
    globalThis.document = { querySelector: () => ({ content: 'token-123' }) };
    globalThis.fetch = fetchImpl;
}

test('postJson sends JSON with the CSRF token and returns the parsed body', async () => {
    let sent;

    stubBrowser(async (url, init) => {
        sent = { url, init };

        return { ok: true, status: 201, json: async () => ({ reference: 'RS-ABC123' }) };
    });

    const result = await postJson('/demo/reservation', { guests: 2 });

    assert.deepEqual(result, { ok: true, status: 201, body: { reference: 'RS-ABC123' } });
    assert.equal(sent.init.method, 'POST');
    assert.equal(sent.init.headers['X-CSRF-TOKEN'], 'token-123');
    assert.equal(sent.init.body, JSON.stringify({ guests: 2 }));
});

test('postJson reports an HTTP error as a result, not an exception', async () => {
    stubBrowser(async () => ({ ok: false, status: 422, json: async () => ({ errors: { guests: ['Check this field.'] } }) }));

    const result = await postJson('/demo/reservation', {});

    assert.equal(result.ok, false);
    assert.equal(result.status, 422);
});

test('postJson throws an Error with status 0 when the network fails', async () => {
    stubBrowser(async () => {
        throw new TypeError('fetch failed');
    });

    await assert.rejects(postJson('/demo/barista/message', {}), (error) => error.status === 0);
});

test('getJson puts the query in the URL', async () => {
    let requested;

    stubBrowser(async (url) => {
        requested = url;

        return { ok: true, status: 200, json: async () => ({ messages: [] }) };
    });

    await getJson('/demo/barista/history', { guest_token: 'abc' });

    assert.equal(requested, '/demo/barista/history?guest_token=abc');
});
