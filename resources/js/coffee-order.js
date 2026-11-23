import { getJson } from './shared/api.js';

function initCoffeeOrder() {
    const form = document.querySelector('[data-coffee-order-form]');
    if (form) {
        const button = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', () => { button.disabled = true; });
        // Re-enable a form restored through the browser's back/forward cache.
        window.addEventListener('pageshow', () => { button.disabled = false; });
    }

    const tracker = document.querySelector('[data-coffee-tracker]');
    if (!tracker || tracker.dataset.status === 'ready') return;

    const error = tracker.querySelector('[data-coffee-error]');
    const machine = tracker.querySelector('.coffee-machine');
    let timer;
    let stopped = false;
    let generation = 0;

    async function poll() {
        if (stopped) return;
        const currentGeneration = generation;
        try {
            const response = await getJson(tracker.dataset.statusUrl, {}, { timeoutMs: 10000 });
            if (stopped || currentGeneration !== generation) return;
            if ([401, 403, 404, 419].includes(response.status)) {
                error.textContent = tracker.dataset.expired;
                error.hidden = false;
                stopped = true;
                return;
            }
            const data = response.body;
            if (!response.ok || !['queued', 'brewing', 'ready'].includes(data.status)
                || !Number.isFinite(data.progress) || data.progress < 0 || data.progress > 100) {
                throw new Error('Invalid coffee status');
            }

            tracker.dataset.status = data.status;
            tracker.querySelector('[data-coffee-status]').textContent = tracker.dataset[data.status];
            tracker.querySelector('[data-coffee-queue]').hidden = data.status !== 'queued';
            tracker.querySelector('[data-coffee-position]').textContent = data.queue_position;
            tracker.querySelector('[data-coffee-percent]').textContent = data.progress;
            tracker.querySelector('progress').value = data.progress;
            tracker.querySelector('[data-coffee-ready]').hidden = data.status !== 'ready';
            tracker.querySelectorAll('[data-coffee-step]').forEach((step) => {
                if (step.dataset.coffeeStep === data.status) step.setAttribute('aria-current', 'step');
                else step.removeAttribute('aria-current');
            });
            machine.dataset.state = data.status;
            machine.style.setProperty('--coffee-progress', data.progress / 100);
            error.hidden = true;
            stopped = data.status === 'ready';
        } catch {
            if (stopped || currentGeneration !== generation) return;
            // Preserve the last confirmed state, including the animation's fill level.
            error.textContent = tracker.dataset.retry;
            error.hidden = false;
        }
        if (!stopped) timer = window.setTimeout(poll, 3000);
    }

    timer = window.setTimeout(poll, 3000);
    window.addEventListener('pagehide', () => {
        stopped = true;
        generation += 1;
        window.clearTimeout(timer);
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted && tracker.dataset.status !== 'ready') {
            stopped = false;
            window.clearTimeout(timer);
            poll();
        }
    });
}

document.addEventListener('DOMContentLoaded', initCoffeeOrder);
