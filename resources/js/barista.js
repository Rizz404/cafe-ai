/**
 * AI Barista chat panel. Vanilla JS, no framework: sends guest messages to
 * the Laravel backend and renders both plain text and the structured
 * ui_payload the AI's tools attach (menu cards, seating options, table
 * availability, reservation confirmations, handover notices) as real DOM,
 * not markdown-in-a-bubble.
 */
import { getJson, postJson } from './shared/api.js';
import { escapeHtml } from './shared/dom.js';

function initBarista() {
    const root = document.getElementById('barista-app');
    const widget = document.querySelector('[data-chat-widget]');
    if (!root || !widget) return;

    const config = {
        startUrl: root.dataset.startUrl,
        messageUrl: root.dataset.messageUrl,
        historyUrl: root.dataset.historyUrl,
        storageKey: root.dataset.storageKey,
        locale: root.dataset.locale,
        currency: root.dataset.currency,
        homeUrl: root.dataset.homeUrl,
        itemUrlTemplate: root.dataset.itemUrl,
        staffUrl: root.dataset.staffUrl,
        reservationUrlTemplate: root.dataset.reservationUrl,
        terms: JSON.parse(root.dataset.terms || '{}'),
        labels: {
            placeholder: root.dataset.labelPlaceholder,
            send: root.dataset.labelSend,
            open: root.dataset.labelOpen,
            close: root.dataset.labelClose,
            intro: root.dataset.labelIntro,
            soldOut: root.dataset.labelSoldOut,
            guests: root.dataset.labelGuests,
            availableSlots: root.dataset.labelAvailableSlots,
            noAvailability: root.dataset.labelNoAvailability,
            reservationReceived: root.dataset.labelReservationReceived,
            reference: root.dataset.labelReference,
            feeFree: root.dataset.labelFeeFree,
            fee: root.dataset.labelFee,
            thinking: root.dataset.labelThinking,
            handedOver: root.dataset.labelHandedOver,
            statusSent: root.dataset.labelStatusSent,
            statusWaiting: root.dataset.labelStatusWaiting,
            statusReplied: root.dataset.labelStatusReplied,
            draftTitle: root.dataset.labelDraftTitle,
            draftBody: root.dataset.labelDraftBody,
            draftKeep: root.dataset.labelDraftKeep,
            draftDiscard: root.dataset.labelDraftDiscard,
            viewDetails: root.dataset.labelViewDetails,
            itemDetailsQuestion: root.dataset.labelItemDetailsQuestion,
            reserveTable: root.dataset.labelReserveTable,
            reserveSlotQuestion: root.dataset.labelReserveSlotQuestion,
            menuHeading: root.dataset.labelMenuHeading,
            staff: root.dataset.labelStaff,
            error: root.dataset.labelError,
            slow: root.dataset.labelSlow,
            retry: root.dataset.labelRetry,
        },
    };

    const messagesEl = root.querySelector('[data-messages]');
    const formEl = root.querySelector('[data-chat-form]');
    const inputEl = root.querySelector('[data-chat-input]');
    const submitEl = root.querySelector('[data-chat-submit]');
    const statusBanner = root.querySelector('[data-status-banner]');
    const chatStatus = root.querySelector('[data-chat-status]');
    const thinkingIndicator = root.querySelector('[data-thinking-indicator]');
    const draftDialog = root.querySelector('[data-draft-dialog]');
    const draftKeep = root.querySelector('[data-draft-keep]');
    const draftDiscard = root.querySelector('[data-draft-discard]');
    const chatLog = root.querySelector('#barista-chat-log');
    const closeButton = widget.querySelector('[data-chat-close]');
    const launcher = widget.querySelector('[data-chat-launcher]');

    let ready = false;
    let busy = false;
    let handedOver = false;
    let knownMessageCount = 0;
    let pollTimer = null;
    let isOpen = false;
    let draftDialogOpen = false;

    function setChatStatus(text, tone = '') {
        if (!chatStatus) return;

        chatStatus.textContent = text || '';
        chatStatus.className = `chat-status${text ? ` is-${tone}` : ''}`;
        chatStatus.hidden = !text;
    }

    function showDraftDialog() {
        if (!draftDialog) return;

        draftDialogOpen = true;
        draftDialog.hidden = false;
        draftDialog.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => draftKeep?.focus());
    }

    function hideDraftDialog({ focusInput = false } = {}) {
        if (!draftDialog) return;

        draftDialogOpen = false;
        draftDialog.hidden = true;
        draftDialog.setAttribute('aria-hidden', 'true');
        if (focusInput) inputEl.focus();
    }

    function syncPanelState() {
        root.classList.toggle('is-open', isOpen);
        root.dataset.chatState = isOpen ? 'open' : 'closed';
        chatLog?.setAttribute('aria-hidden', String(!isOpen));
        formEl?.setAttribute('aria-hidden', String(!isOpen));
        launcher?.setAttribute('aria-expanded', String(isOpen));
    }

    function openPanel({ focus = false } = {}) {
        isOpen = true;
        syncPanelState();
        scrollToBottom();

        if (focus) {
            requestAnimationFrame(() => inputEl.focus());
        }
    }

    function finishClosePanel({ restoreFocus = true } = {}) {
        isOpen = false;
        syncPanelState();
        hideDraftDialog();
        if (restoreFocus) launcher?.focus();
    }

    function closePanel({ confirmDraft = true, restoreFocus = true } = {}) {
        if (confirmDraft && inputEl?.value.trim()) {
            showDraftDialog();
            return;
        }

        finishClosePanel({ restoreFocus });
    }

    closeButton?.addEventListener('click', () => closePanel());
    launcher?.addEventListener('click', () => openPanel({ focus: true }));
    draftKeep?.addEventListener('click', () => hideDraftDialog({ focusInput: true }));
    draftDiscard?.addEventListener('click', () => {
        inputEl.value = '';
        finishClosePanel();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (draftDialogOpen) {
            hideDraftDialog({ focusInput: true });
            return;
        }

        if (isOpen) closePanel();
    });
    syncPanelState();

    const numberLocale = config.locale === 'ja' ? 'ja-JP' : config.locale === 'en' ? 'en-US' : 'id-ID';

    function money(value) {
        const n = Number(value) || 0;
        return `${config.currency} ${new Intl.NumberFormat(numberLocale, { maximumFractionDigits: 0 }).format(n)}`;
    }

    function formatDate(iso) {
        const [year, month, day] = String(iso).split('-').map(Number);
        if (!year) return iso;
        return new Intl.DateTimeFormat(numberLocale, { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(year, month - 1, day));
    }

    /** Text from the database is shown as text, never as markup. */
    const esc = escapeHtml;

    function term(group, code) {
        return config.terms[group]?.[code] || String(code).replace(/_/g, ' ');
    }

    // Cloudflare drops a request that is still unanswered after 100s, so give
    // up just before that and let the guest retry instead of waiting forever.
    const API_TIMEOUT_MS = 95000;

    async function api(url, body) {
        const result = await postJson(url, body, { timeoutMs: API_TIMEOUT_MS });

        if (!result.ok) {
            const error = new Error(result.body.message || `Request failed (${result.status})`);
            error.status = result.status;
            throw error;
        }

        return result.body;
    }

    function avatarMark() {
        const el = document.createElement('span');
        el.className = 'chat-host-avatar';
        el.setAttribute('aria-hidden', 'true');
        return el;
    }

    function bubble(role, text) {
        const wrap = document.createElement('div');
        wrap.className =
            role === 'guest' ? 'flex justify-end' : 'flex items-end justify-start gap-2';

        if (role !== 'guest') {
            wrap.appendChild(avatarMark());
        }

        const inner = document.createElement('div');
        inner.className =
            role === 'guest'
                ? 'max-w-[85%] rounded-2xl rounded-br-sm bg-stone-900 px-3 py-2 text-sm text-white'
                : 'max-w-[85%] rounded-2xl rounded-bl-sm bg-stone-100 px-3 py-2 text-sm text-stone-900';
        inner.textContent = text;

        wrap.appendChild(inner);
        return wrap;
    }

    function card(children) {
        const wrap = document.createElement('div');
        wrap.className = 'flex justify-start';
        const inner = document.createElement('div');
        inner.className = 'w-full max-w-[92%] space-y-2';
        children.forEach((c) => inner.appendChild(c));
        wrap.appendChild(inner);
        return wrap;
    }

    function pill(text, tone = '') {
        const tones = {
            '': 'border-stone-200 bg-stone-50 text-stone-600',
            alert: 'border-rose-200 bg-rose-50 text-rose-800',
            accent: 'border-amber-300 bg-amber-50 text-amber-900',
        };
        return `<span class="rounded-full border px-2 py-0.5 text-[11px] ${tones[tone]}">${esc(text)}</span>`;
    }

    function menuResultCard(item) {
        const el = document.createElement('div');
        el.className = 'overflow-hidden rounded-xl border border-stone-200 bg-white';
        const tags = (item.tags || []).filter((tag) => config.terms.tag?.[tag]).slice(0, 3)
            .map((tag) => pill(term('tag', tag), ['signature', 'bestseller'].includes(tag) ? 'accent' : '')).join('');
        el.innerHTML = `
            ${item.image_url ? `<img src="${esc(item.image_url)}" alt="${esc(item.name)}" class="aspect-[16/9] w-full object-cover">` : ''}
            <div class="p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-stone-900">${esc(item.name)}</p>
                    ${item.is_sold_out ? pill(config.labels.soldOut, 'alert') : ''}
                </div>
                <p class="mt-1 text-sm font-semibold text-stone-900">${money(item.price)}</p>
                ${tags ? `<div class="mt-2 flex flex-wrap gap-1">${tags}</div>` : ''}
                <button type="button" class="mt-2 w-full rounded-lg border border-stone-300 px-2 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-50" data-detail-slug="${esc(item.menu_item_slug)}">
                    ${esc(config.labels.viewDetails)}
                </button>
            </div>
        `;
        el.querySelector('[data-detail-slug]').addEventListener('click', () => {
            sendMessage(config.labels.itemDetailsQuestion.replace(':item', item.name));
        });
        return el;
    }

    function menuDetailCard(item) {
        const el = document.createElement('div');
        el.className = 'overflow-hidden rounded-xl border border-stone-200 bg-white';
        const tags = (item.tags || []).filter((tag) => config.terms.tag?.[tag]).map((tag) => pill(term('tag', tag))).join('');
        const allergens = (item.allergens || []).map((code) => pill(term('allergen', code), 'alert')).join('');

        el.innerHTML = `
            ${item.image_url ? `<img src="${esc(item.image_url)}" alt="${esc(item.name)}" class="aspect-[16/9] w-full object-cover">` : ''}
            <div class="p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-stone-900">${esc(item.name)}</p>
                    ${item.is_sold_out ? pill(config.labels.soldOut, 'alert') : ''}
                </div>
                <p class="mt-1 text-xs leading-relaxed text-stone-500">${esc(item.description || '')}</p>
                <p class="mt-2 text-base font-semibold text-stone-900">${money(item.price)}${item.serving ? ` <span class="text-xs font-normal text-stone-500">· ${esc(term('serving', item.serving))}</span>` : ''}</p>
                ${tags ? `<div class="mt-2 flex flex-wrap gap-1">${tags}</div>` : ''}
                ${allergens ? `<div class="mt-2 flex flex-wrap gap-1">${allergens}</div>` : ''}
            </div>
        `;
        return el;
    }

    function feeText(feeValue) {
        return Number(feeValue) > 0 ? `${config.labels.fee}: ${money(feeValue)}` : config.labels.feeFree;
    }

    function seatingResultCard(area, payload) {
        const el = document.createElement('div');
        el.className = 'overflow-hidden rounded-xl border border-stone-200 bg-white';
        const capacity = `${area.min_guests > 1 ? `${area.min_guests}–` : ''}${area.max_guests} ${config.labels.guests}`;
        el.innerHTML = `
            ${area.thumbnail_url ? `<img src="${esc(area.thumbnail_url)}" alt="${esc(area.name)}" class="aspect-[16/9] w-full object-cover">` : ''}
            <div class="p-3">
                <p class="font-medium text-stone-900">${esc(area.name)}</p>
                <p class="mt-0.5 text-xs text-stone-500">${esc(term('area_type', area.area_type))} · ${esc(capacity)}</p>
                <p class="mt-1 text-xs text-stone-500">${esc(feeText(area.reservation_fee))}</p>
                <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-stone-400">${esc(config.labels.availableSlots)} · ${esc(formatDate(payload.date))}</p>
                <div class="mt-1 flex flex-wrap gap-1.5" data-slots></div>
            </div>
        `;
        const slots = el.querySelector('[data-slots]');
        area.available_slots.forEach((slot) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded-full border border-stone-300 px-2.5 py-1 text-xs font-medium tabular-nums text-stone-700 hover:bg-amber-50';
            button.textContent = slot.time_range;
            button.addEventListener('click', () => {
                sendMessage(config.labels.reserveSlotQuestion
                    .replace(':area', area.name)
                    .replace(':date', formatDate(payload.date))
                    .replace(':time', slot.time_range)
                    .replace(':guests', payload.guests));
            });
            slots.appendChild(button);
        });
        return el;
    }

    function availabilityCard(payload) {
        const el = document.createElement('div');
        el.className = 'rounded-xl border border-stone-200 bg-white p-3';

        if (!payload.available) {
            el.innerHTML = `<p class="text-sm text-stone-600">${esc(config.labels.noAvailability)}</p>`;
            return el;
        }

        const q = payload.quote;
        el.innerHTML = `
            <p class="font-medium text-stone-900">${esc(q.name)}</p>
            <p class="text-xs text-stone-500">${esc(formatDate(q.date))} · ${esc(q.time_range)}</p>
            <p class="mt-1 text-sm font-semibold text-stone-900">${esc(feeText(q.reservation_fee))}</p>
        `;
        return el;
    }

    function reservationConfirmationCard(payload) {
        const r = payload.reservation;
        const el = document.createElement('div');
        el.className = 'rounded-xl border border-emerald-200 bg-emerald-50 p-3';
        el.innerHTML = `
            <p class="text-sm font-semibold text-emerald-900">${esc(config.labels.reservationReceived)}</p>
            <p class="mt-1 text-xs text-emerald-800">${esc(config.labels.reference)}: ${esc(r.reservation_reference)} · ${esc(r.name)}</p>
            <p class="text-xs text-emerald-800">${esc(formatDate(r.date))} · ${esc(r.time_range)} · ${esc(r.guests)} ${esc(config.labels.guests)}</p>
            <p class="mt-1 text-sm font-semibold text-emerald-900">${esc(feeText(r.deposit_total))}</p>
        `;
        return el;
    }

    function handoverCard() {
        const el = document.createElement('div');
        el.className = 'rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800';
        el.textContent = config.labels.handedOver;
        return el;
    }

    function renderUiPayload(uiPayload) {
        if (!uiPayload || !uiPayload.length) return;

        uiPayload.forEach((payload) => {
            if (payload.type === 'menu_results') {
                messagesEl.appendChild(card(payload.items.map(menuResultCard)));
            } else if (payload.type === 'menu_detail') {
                messagesEl.appendChild(card([menuDetailCard(payload.item)]));
            } else if (payload.type === 'seating_results') {
                messagesEl.appendChild(card(payload.areas.map((area) => seatingResultCard(area, payload))));
            } else if (payload.type === 'availability') {
                messagesEl.appendChild(card([availabilityCard(payload)]));
            } else if (payload.type === 'reservation_confirmation') {
                messagesEl.appendChild(card([reservationConfirmationCard(payload)]));
            } else if (payload.type === 'handover') {
                messagesEl.appendChild(card([handoverCard()]));
            }
        });
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setBusy(value) {
        busy = value;
        const disabled = busy || !ready || handedOver;
        inputEl.disabled = disabled;
        submitEl.disabled = disabled;
        if (launcher) launcher.disabled = busy || !ready;
        root.setAttribute('aria-busy', String(busy));
        document.body.classList.toggle('is-thinking', busy);
        if (thinkingIndicator) {
            thinkingIndicator.hidden = !busy;
        }
        document.querySelectorAll('[data-hero-quick-message], [data-ask-ai-button], [data-quick-message]')
            .forEach((button) => { button.disabled = disabled; });
        submitEl.textContent = busy ? config.labels.thinking : config.labels.send;
        if (busy) scrollToBottom();
    }

    function showHandedOverBanner() {
        handedOver = true;
        statusBanner.textContent = config.labels.handedOver;
        statusBanner.classList.remove('hidden');
        setChatStatus(config.labels.statusWaiting, 'waiting');
        setBusy(busy);
        startPolling();
    }

    function clearHandedOverBanner() {
        handedOver = false;
        statusBanner.classList.add('hidden');
        setBusy(busy);
        stopPolling();
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(pollForStaffReplies, 4000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    async function pollForStaffReplies() {
        const guestToken = localStorage.getItem(config.storageKey);
        if (!guestToken) return;

        try {
            const response = await getJson(config.historyUrl, { guest_token: guestToken });
            if (!response.ok) return;

            const data = response.body;

            if (data.messages.length > knownMessageCount) {
                const newMessages = data.messages.slice(knownMessageCount);
                newMessages.forEach(renderMessage);
                if (newMessages.some((message) => message.role === 'staff')) {
                    setChatStatus(config.labels.statusReplied, 'replied');
                }
                window.cafeSound?.play('incoming');
                knownMessageCount = data.messages.length;
                scrollToBottom();
            }

            if (data.status !== 'handed_over') {
                clearHandedOverBanner();
            }
        } catch {
            // transient network hiccup — try again on the next tick
        }
    }

    function renderMessage(message) {
        if (message.role === 'guest') {
            messagesEl.appendChild(bubble('guest', message.content));
        } else if (message.role === 'assistant') {
            if (message.content) {
                messagesEl.appendChild(bubble('assistant', message.content));
            }
            renderUiPayload(message.ui_payload);
            const chips = actionChips(message.suggested_actions);
            if (chips) messagesEl.appendChild(chips);
        } else if (message.role === 'staff') {
            messagesEl.appendChild(card([staffBubble(message.content)]));
            setChatStatus(config.labels.statusReplied, 'replied');
        } else if (message.role === 'system') {
            messagesEl.appendChild(bubble('assistant', message.content));
        }
    }

    function quickMenuCard() {
        const template = document.querySelector('[data-quick-menu-items]');
        if (!template) return null;

        const el = document.createElement('div');
        el.className = 'overflow-hidden rounded-xl border border-stone-200 bg-white';

        const heading = document.createElement('p');
        heading.className = 'border-b border-stone-100 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-stone-400';
        heading.textContent = config.labels.menuHeading;
        el.appendChild(heading);

        const list = document.createElement('div');
        list.className = 'divide-y divide-stone-100';

        template.content.querySelectorAll('[data-quick-message]').forEach((source) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'flex w-full items-center justify-between px-3 py-2.5 text-left text-sm text-stone-700 hover:bg-amber-50';
            item.innerHTML = `<span>${esc(source.textContent)}</span><span class="text-stone-300">›</span>`;
            item.addEventListener('click', () => sendMessage(source.dataset.quickMessage));
            list.appendChild(item);
        });

        el.appendChild(list);
        return el;
    }

    function staffBubble(text) {
        const el = document.createElement('div');
        el.className = 'rounded-2xl rounded-bl-sm border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-900';
        el.innerHTML = `<p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-500">${esc(config.labels.staff)}</p>${esc(text)}`;
        return el;
    }

    const sceneNames = {
        home: 'home', info: 'home', menu: 'menu', 'menu-item': 'menu_item', seating: 'seating', 'seating-area': 'seating_area',
        facilities: 'facilities', facility: 'facility_detail', reservation: 'reservation', staff: 'handover',
    };

    /**
     * Where the guest is in the cafe, so the barista can answer for "this
     * dish" or the reservation on screen. Never includes name or contact data.
     */
    function uiContext() {
        const pageScene = document.querySelector('[data-stage]')?.dataset.scene || 'home';
        const context = { scene: sceneNames[pageScene] || 'home' };

        const lastSegment = decodeURIComponent(window.location.pathname.split('/').pop());
        if (pageScene === 'menu-item') context.selected_menu_item = lastSegment;
        if (pageScene === 'seating-area') context.selected_seating_area = lastSegment;
        if (pageScene === 'facility') context.selected_facility = Number(lastSegment);

        if (pageScene === 'reservation') {
            try {
                const draft = JSON.parse(sessionStorage.getItem(`reservation_draft_${root.dataset.cafeSlug}`) || 'null');
                const { reservation_date, time_slot, guests, occasion, seating_area_slug } = draft?.values || {};
                context.reservation = { reservation_date, time_slot, guests, occasion, seating_area_slug };
                if (seating_area_slug) context.selected_seating_area = seating_area_slug;
            } catch { /* no draft to share */ }
        }

        return context;
    }

    function actionChips(actions) {
        if (!actions || !actions.length) return null;

        const itemUrl = (slug) => config.itemUrlTemplate.replace('__SLUG__', encodeURIComponent(slug));
        const reservationUrl = (slug) => (slug
            ? config.reservationUrlTemplate.replace('__SLUG__', encodeURIComponent(slug))
            : config.reservationUrlTemplate.replace(/[?&]area=__SLUG__/, ''));
        const targets = {
            view_item: [config.labels.viewDetails, (action) => itemUrl(action.item)],
            reserve: [config.labels.reserveTable, (action) => reservationUrl(action.area)],
            staff: [config.labels.staff, () => config.staffUrl],
        };

        const wrap = document.createElement('div');
        wrap.className = 'flex flex-wrap gap-2 pl-10';

        actions.forEach((action) => {
            const target = targets[action.action];
            if (!target) return;
            const link = document.createElement('a');
            link.href = target[1](action);
            link.className = 'rounded-full border border-stone-300 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-50';
            link.textContent = target[0];
            link.addEventListener('click', (event) => {
                closePanel({ confirmDraft: false, restoreFocus: false });

                // Another page: walk there the way the menu does. A panel on
                // this page is just a hash change, so leave it to the browser.
                if (link.pathname !== window.location.pathname && window.cafeStage) {
                    event.preventDefault();
                    window.cafeStage.leave(link.href);
                }
            });
            wrap.appendChild(link);
        });

        return wrap.childElementCount ? wrap : null;
    }

    function errorCard(text, err) {
        const el = document.createElement('div');
        el.className = 'rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900';

        const message = document.createElement('p');
        message.textContent = err.status === 429 ? config.labels.slow : config.labels.error;

        const actions = document.createElement('div');
        actions.className = 'mt-3 flex flex-wrap gap-2';

        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'rounded-full bg-stone-900 px-3 py-1.5 text-xs font-medium text-white';
        retry.textContent = config.labels.retry;
        retry.addEventListener('click', () => {
            el.remove();
            sendMessage(text, { retry: true });
        });

        const staff = document.createElement('a');
        staff.href = config.staffUrl;
        staff.className = 'rounded-full border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-900';
        staff.textContent = config.labels.staff;
        staff.addEventListener('click', (event) => {
            closePanel({ confirmDraft: false, restoreFocus: false });

            if (window.cafeStage) {
                event.preventDefault();
                window.cafeStage.leave(staff.href);
            }
        });

        actions.append(retry, staff);
        el.append(message, actions);
        return el;
    }

    async function sendMessage(text, { retry = false } = {}) {
        if (!text.trim() || handedOver || busy || !ready) return;

        const guestToken = localStorage.getItem(config.storageKey);
        if (!guestToken) return;

        openPanel();
        if (!retry) messagesEl.appendChild(bubble('guest', text));
        window.cafeSound?.play('sent');
        scrollToBottom();
        setBusy(true);

        try {
            const data = await api(config.messageUrl, { guest_token: guestToken, message: text, ...uiContext() });
            renderMessage(data.message);
            setChatStatus(config.labels.statusSent, 'sent');
            window.cafeSound?.play('incoming');
            knownMessageCount += 2; // the guest message just sent + the reply just rendered
            if (data.status === 'handed_over') {
                showHandedOverBanner();
            }
        } catch (err) {
            messagesEl.appendChild(errorCard(text, err));
        } finally {
            setBusy(false);
            scrollToBottom();
        }
    }

    async function boot() {
        let guestToken = localStorage.getItem(config.storageKey);

        if (!guestToken) {
            const data = await api(config.startUrl, { locale: config.locale });
            guestToken = data.guest_token;
            localStorage.setItem(config.storageKey, guestToken);
            messagesEl.appendChild(bubble('assistant', config.labels.intro));
            const menu = quickMenuCard();
            if (menu) messagesEl.appendChild(card([menu]));
            return;
        }

        try {
            const response = await getJson(config.historyUrl, { guest_token: guestToken });

            if (!response.ok) throw new Error('history_unavailable');

            const data = response.body;

            if (!data.messages.length) {
                messagesEl.appendChild(bubble('assistant', config.labels.intro));
                const menu = quickMenuCard();
                if (menu) messagesEl.appendChild(card([menu]));
            } else {
                data.messages.forEach(renderMessage);
            }
            knownMessageCount = data.messages.length;

            if (data.status === 'handed_over') {
                showHandedOverBanner();
            }
            if (data.messages.some((message) => message.role === 'staff')) {
                setChatStatus(config.labels.statusReplied, 'replied');
            }
        } catch {
            localStorage.removeItem(config.storageKey);
            return boot();
        }

        scrollToBottom();
    }

    formEl.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!ready || busy || handedOver || !inputEl.value.trim()) return;
        const text = inputEl.value;
        inputEl.value = '';
        sendMessage(text);
    });

    // Quick-start buttons on a scene — the same topics as the in-chat menu,
    // always visible up front so guests see what the AI can do immediately.
    document.querySelectorAll('[data-hero-quick-message]').forEach((button) => {
        button.addEventListener('click', () => {
            openPanel();
            sendMessage(button.dataset.heroQuickMessage);
        });
    });

    // A menu pick in the same scene: the barista answers right where the guest stands.
    window.addEventListener('barista:ask', (event) => sendMessage(event.detail.message));

    setBusy(true);
    boot().then(() => {
        ready = true;
        scrollToBottom();
    }).catch(() => {
        statusBanner.textContent = root.dataset.labelConnectionError;
        statusBanner.classList.remove('hidden');
        messagesEl.appendChild(bubble('assistant', config.labels.intro));
    }).finally(() => {
        setBusy(false);

        // The guest chose this topic from the menu on the previous scene:
        // let the new scene settle for a beat, then carry on the conversation.
        const topic = window.takePendingBaristaTopic?.();
        if (topic && ready) window.setTimeout(() => sendMessage(topic), 650);
    });
}

document.addEventListener('DOMContentLoaded', initBarista);

function initDetailGallery() {
    document.querySelectorAll('[data-detail-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-detail-gallery-main]');
        const thumbs = [...gallery.querySelectorAll('[data-detail-thumb]')];
        if (!main) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                main.src = thumb.dataset.src;
                main.alt = thumb.dataset.alt || '';
                thumbs.forEach((other) => other.toggleAttribute('aria-current', other === thumb));
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', initDetailGallery);
