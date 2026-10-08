/**
 * The one place the guest-facing scripts talk to the Laravel backend: JSON in,
 * JSON out, with the CSRF token. Every function resolves to
 * { ok, status, body } for HTTP answers; only a network failure or a timeout
 * throws (an Error whose status is 0 or 408).
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * @param {string} url
 * @param {object} [payload]
 * @param {{ timeoutMs?: number }} [options]
 * @returns {Promise<{ ok: boolean, status: number, body: any }>}
 */
export async function postJson(url, payload = {}, { timeoutMs = null } = {}) {
    let response;

    try {
        response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
            ...(timeoutMs ? { signal: AbortSignal.timeout(timeoutMs) } : {}),
        });
    } catch (err) {
        const timedOut = err.name === 'TimeoutError';
        const error = new Error(timedOut ? 'Request timed out' : 'Network error');
        error.status = timedOut ? 408 : 0;
        throw error;
    }

    return { ok: response.ok, status: response.status, body: await response.json().catch(() => ({})) };
}

/**
 * @param {string} url
 * @param {Record<string, string>} [query]
 * @returns {Promise<{ ok: boolean, status: number, body: any }>}
 */
export async function getJson(url, query = {}) {
    const search = new URLSearchParams(query).toString();
    const response = await fetch(search ? `${url}?${search}` : url, {
        headers: { Accept: 'application/json' },
    });

    return { ok: response.ok, status: response.status, body: await response.json().catch(() => ({})) };
}
