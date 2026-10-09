/**
 * Guided table-reservation wizard: five short steps in its own scene — date
 * and time, guests, seating, contact, summary — then a reference number and
 * WhatsApp / phone / email hand-over. Progress survives leaving the page and
 * switching language (sessionStorage), and every rule is enforced again on the
 * server, which is the only source of availability.
 */
import { postJson } from './shared/api.js';

function initReservationWizard() {
    const root = document.querySelector('[data-wizard]');
    if (!root) return;

    const labels = JSON.parse(root.querySelector('[data-wizard-labels]').textContent);
    const form = root.querySelector('[data-wizard-form]');
    const flow = root.querySelector('[data-wizard-flow]');
    const done = root.querySelector('[data-wizard-done]');
    const steps = [...root.querySelectorAll('[data-step]')];
    const progress = [...root.querySelectorAll('[data-progress-step]')];
    const stepLabel = root.querySelector('[data-wizard-step-label]');
    const backButton = root.querySelector('[data-wizard-back]');
    const nextButton = root.querySelector('[data-wizard-next]');
    const submitButton = root.querySelector('[data-wizard-submit]');
    const errorBox = root.querySelector('[data-wizard-error]');
    const errorText = root.querySelector('[data-wizard-error-text]');
    const alternativesBox = root.querySelector('[data-wizard-alternatives]');
    const areaOptions = [...root.querySelectorAll('[data-area-option]')];

    const config = {
        quoteUrl: root.dataset.quoteUrl,
        submitUrl: root.dataset.submitUrl,
        locale: root.dataset.locale,
        currency: root.dataset.currency,
        today: root.dataset.today,
        maxDays: Number(root.dataset.maxDays),
        draftKey: `reservation_draft_${root.dataset.cafeSlug}`,
        tokenKey: `barista_token_${root.dataset.cafeSlug}`,
    };

    const fieldStep = {
        reservation_date: 1, time_slot: 1, guests: 2, occasion: 2, seating_area_slug: 3,
        guest_name: 4, contact_type: 4, contact_value: 4, special_request: 4,
    };

    let current = 1;
    let quote = null;
    let busy = false;

    const text = (template, values) => Object.entries(values).reduce((out, [key, value]) => out.replace(`:${key}`, value), template);
    const money = (value) => `${config.currency} ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0)}`;
    const feeText = (value) => (Number(value) > 0 ? money(value) : labels.fee_free);

    function formatDate(iso) {
        const [year, month, day] = iso.split('-').map(Number);
        return new Intl.DateTimeFormat(config.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day));
    }

    function daysFromToday(iso) {
        const [y1, m1, d1] = config.today.split('-').map(Number);
        const [y2, m2, d2] = iso.split('-').map(Number);
        return Math.round((Date.UTC(y2, m2 - 1, d2) - Date.UTC(y1, m1 - 1, d1)) / 86400000);
    }

    function slotLabel(value) {
        return form.querySelector(`input[name="time_slot"][value="${value}"]`)?.nextElementSibling?.textContent || value;
    }

    function values() {
        const data = new FormData(form);
        return {
            reservation_date: data.get('reservation_date') || '',
            time_slot: data.get('time_slot') || '',
            guests: Number(data.get('guests')) || 0,
            occasion: data.get('occasion') || '',
            seating_area_slug: data.get('seating_area_slug') || '',
            guest_name: (data.get('guest_name') || '').trim(),
            contact_type: data.get('contact_type') || 'whatsapp',
            contact_value: (data.get('contact_value') || '').trim(),
            special_request: (data.get('special_request') || '').trim(),
        };
    }

    function saveDraft() {
        try {
            sessionStorage.setItem(config.draftKey, JSON.stringify({ step: current, values: values() }));
        } catch { /* storage unavailable: the wizard still works without it */ }
    }

    function clearDraft() {
        try {
            sessionStorage.removeItem(config.draftKey);
        } catch { /* nothing to clear */ }
    }

    function restoreDraft() {
        try {
            const draft = JSON.parse(sessionStorage.getItem(config.draftKey) || 'null');
            if (!draft) return;
            const { values: saved } = draft;
            ['reservation_date', 'guests', 'occasion', 'guest_name', 'contact_value', 'special_request'].forEach((name) => {
                if (saved[name] !== undefined && saved[name] !== '' && saved[name] !== 0) form.elements[name].value = saved[name];
            });
            if (saved.contact_type) form.elements.contact_type.value = saved.contact_type;
            if (saved.time_slot) form.elements.time_slot.value = saved.time_slot;
            if (saved.seating_area_slug) form.elements.seating_area_slug.value = saved.seating_area_slug;
            current = Math.min(Math.max(Number(draft.step) || 1, 1), steps.length);
        } catch { /* ignore a corrupt draft */ }
    }

    function clearError() {
        errorBox.hidden = true;
        errorText.textContent = '';
        alternativesBox.hidden = true;
        alternativesBox.querySelector('ul').replaceChildren();
    }

    function showError(message, alternatives = []) {
        errorText.textContent = message;
        errorBox.hidden = false;

        const list = alternativesBox.querySelector('ul');
        list.replaceChildren();
        alternatives.forEach((alternative) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'wizard-secondary';
            button.textContent = alternative.type === 'area'
                ? `${alternative.name} · ${feeText(alternative.fee)}`
                : alternative.name;
            button.addEventListener('click', () => {
                if (alternative.type === 'area') form.elements.seating_area_slug.value = alternative.slug;
                else form.elements.time_slot.value = alternative.time_slot;
                refreshAreas();
                clearError();
                saveDraft();
            });
            item.appendChild(button);
            list.appendChild(item);
        });
        alternativesBox.hidden = alternatives.length === 0;
    }

    function selectedOption() {
        const slug = form.elements.seating_area_slug.value;
        return areaOptions.find((option) => option.querySelector('input').value === slug) || null;
    }

    function fits(option, party) {
        return party.guests >= Number(option.dataset.minGuests) && party.guests <= Number(option.dataset.maxGuests);
    }

    function refreshAreas() {
        const party = values();

        areaOptions.forEach((option) => {
            const input = option.querySelector('input');
            const suitable = fits(option, party);
            input.disabled = !suitable;
            option.classList.toggle('is-disabled', !suitable);
            option.querySelector('[data-area-hint]').textContent = suitable ? '' : labels.too_small;
            if (!suitable && input.checked) input.checked = false;
        });
    }

    function validate(step) {
        const data = values();

        if (step === 1) {
            if (!data.reservation_date || data.reservation_date < config.today) return { field: 'reservation_date', message: labels.date_past };
            if (daysFromToday(data.reservation_date) > config.maxDays) return { field: 'reservation_date', message: text(labels.date_far, { max: config.maxDays }) };
            if (!data.time_slot) return { field: 'time_slot', message: labels.slot_unknown };
        }

        if (step === 2) {
            if (data.guests < 1) return { field: 'guests', message: labels.invalid };
        }

        if (step === 3) {
            const chosen = selectedOption();
            if (!chosen) return { field: 'seating_area_slug', message: labels.area_unknown };
            if (!fits(chosen, data)) return { field: 'seating_area_slug', message: labels.too_small };
        }

        if (step === 4) {
            if (!data.guest_name) return { field: 'guest_name', message: labels.invalid };
            const valid = data.contact_type === 'email'
                ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.contact_value)
                : /^\+?[0-9\s\-().]{6,20}$/.test(data.contact_value);
            if (!valid) return { field: 'contact_value', message: data.contact_type === 'email' ? labels.contact_email : labels.contact_phone };
        }

        return null;
    }

    function setBusy(value, label = null) {
        busy = value;
        [backButton, nextButton, submitButton].forEach((button) => { button.disabled = value; });
        if (label) nextButton.firstChild.textContent = `${label} `;
        else nextButton.firstChild.textContent = `${labels.next} `;
    }

    function showStep(step, focus = false) {
        current = step;
        steps.forEach((element, index) => { element.hidden = index + 1 !== step; });
        progress.forEach((element, index) => {
            element.classList.toggle('is-current', index + 1 === step);
            element.classList.toggle('is-done', index + 1 < step);
            if (index + 1 === step) element.setAttribute('aria-current', 'step');
            else element.removeAttribute('aria-current');
        });
        stepLabel.textContent = `${text(labels.step_of, { current: step, total: steps.length })} · ${labels.steps[step - 1]}`;
        backButton.hidden = step === 1;
        nextButton.hidden = step === steps.length;
        submitButton.hidden = step !== steps.length;

        if (step === 2 || step === 3) refreshAreas();
        if (step === 3 && areaOptions.every((option) => option.classList.contains('is-disabled'))) showError(labels.capacity);
        if (step === steps.length) renderSummary();
        if (focus) steps[step - 1].querySelector('input:not([disabled]), textarea, select')?.focus({ preventScroll: true });
        saveDraft();
    }

    function goToField(field) {
        showStep(fieldStep[field] || current, true);
    }

    function post(url, payload) {
        return postJson(url, payload);
    }

    function reservationPayload() {
        const { reservation_date, time_slot, guests, occasion, seating_area_slug } = values();
        return { reservation_date, time_slot, guests, occasion: occasion || null, seating_area_slug, locale: config.locale };
    }

    function handleFailure(result) {
        if (result.status === 422 && result.body.errors) {
            const [field, messages] = Object.entries(result.body.errors)[0];
            showError(messages[0], result.body.alternatives || []);
            if (fieldStep[field] && fieldStep[field] !== current) showStep(fieldStep[field]);
            return;
        }
        showError(labels.error_generic);
    }

    async function next() {
        if (busy || current >= steps.length) return;
        clearError();

        const problem = validate(current);
        if (problem) {
            showError(problem.message);
            form.elements[problem.field]?.focus?.();
            return;
        }

        if (current === 3) {
            setBusy(true, labels.checking);
            try {
                const result = await post(config.quoteUrl, reservationPayload());
                if (!result.ok) {
                    handleFailure(result);
                    return;
                }
                quote = result.body;
            } catch {
                showError(labels.error_network);
                return;
            } finally {
                setBusy(false);
            }
        }

        showStep(current + 1, true);
    }

    function summaryRow(label, value, step) {
        const wrapper = document.createElement('div');
        const term = document.createElement('dt');
        term.textContent = label;
        const detail = document.createElement('dd');
        const span = document.createElement('span');
        span.textContent = value;
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.textContent = labels.edit;
        edit.addEventListener('click', () => showStep(step, true));
        detail.append(span, edit);
        wrapper.append(term, detail);
        return wrapper;
    }

    function renderSummary() {
        const data = values();
        const chosen = selectedOption();
        const occasionLabel = data.occasion ? form.querySelector(`select[name="occasion"] option[value="${data.occasion}"]`)?.textContent : '';

        const rows = [
            summaryRow(labels.dateTime, `${formatDate(data.reservation_date)} · ${slotLabel(data.time_slot)}`, 1),
            summaryRow(labels.guests, `${data.guests}${occasionLabel ? ` · ${occasionLabel}` : ''}`, 2),
            summaryRow(labels.area, chosen ? chosen.dataset.name : '', 3),
            summaryRow(labels.contact, `${data.guest_name} · ${labels[data.contact_type]}: ${data.contact_value}`, 4),
        ];
        if (data.special_request) rows.push(summaryRow(labels.special, data.special_request, 4));

        root.querySelector('[data-summary]').replaceChildren(...rows);
        root.querySelector('[data-summary-total]').textContent = quote ? feeText(quote.fee) : '';

        const spend = root.querySelector('[data-summary-spend]');
        const hasSpend = quote && Number(quote.minimum_spend) > 0;
        spend.hidden = !hasSpend;
        spend.textContent = hasSpend ? `${labels.min_spend}: ${money(quote.minimum_spend)}` : '';
    }

    function showDone(result) {
        flow.hidden = true;
        done.hidden = false;
        root.querySelector('[data-done-reference]').textContent = result.reference;

        [['whatsapp', 'whatsapp_url'], ['phone', 'phone_url'], ['email', 'email_url']].forEach(([name, key]) => {
            const link = root.querySelector(`[data-done-${name}]`);
            const url = result.handover?.[key];
            link.hidden = !url;
            if (url) link.href = url;
        });
    }

    async function submit(event) {
        event.preventDefault();
        if (busy || current !== steps.length) return;
        clearError();

        const problem = [1, 2, 3, 4].map(validate).find(Boolean);
        if (problem) {
            showError(problem.message);
            goToField(problem.field);
            return;
        }

        setBusy(true);
        submitButton.textContent = labels.sending;

        try {
            const result = await post(config.submitUrl, {
                ...reservationPayload(),
                guest_name: values().guest_name,
                contact_type: values().contact_type,
                contact_value: values().contact_value,
                special_request: values().special_request,
                guest_token: localStorage.getItem(config.tokenKey) || undefined,
            });

            if (!result.ok) {
                handleFailure(result);
                return;
            }

            clearDraft();
            showDone(result.body);
            window.cafeSound?.play('thanks');
        } catch {
            showError(labels.error_network);
        } finally {
            setBusy(false);
            submitButton.textContent = labels.submit;
        }
    }

    function reset() {
        form.reset();
        quote = null;
        clearError();
        done.hidden = true;
        flow.hidden = false;
        clearDraft();
        showStep(1, true);
    }

    function preselect(slug) {
        if (!slug || flow.hidden) return;

        refreshAreas();
        const option = areaOptions.find((candidate) => candidate.querySelector('input').value === slug);
        if (option && !option.querySelector('input').disabled) {
            form.elements.seating_area_slug.value = slug;
            refreshAreas();
            saveDraft();
        }
    }

    form.addEventListener('input', () => { if (current <= 3) refreshAreas(); saveDraft(); });
    form.addEventListener('change', () => { refreshAreas(); saveDraft(); });
    form.addEventListener('submit', submit);
    nextButton.addEventListener('click', next);
    backButton.addEventListener('click', () => { clearError(); showStep(Math.max(1, current - 1), true); });
    root.querySelector('[data-wizard-reset]').addEventListener('click', reset);
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && current < steps.length) {
            event.preventDefault();
            next();
        }
    });

    restoreDraft();
    refreshAreas();
    showStep(current);
    preselect(root.dataset.preselectArea || '');
}

document.addEventListener('DOMContentLoaded', initReservationWizard);
