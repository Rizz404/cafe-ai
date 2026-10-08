/**
 * The AI barista introducing a scene: types the intro like speech, animates
 * the avatar and sound bars while "speaking", and can read it aloud with the
 * browser's speech synthesis when the guest asks (never automatically).
 * The full text is always in the DOM for screen readers and no-JS visitors.
 */
function initNarrators() {
    const narrators = [...document.querySelectorAll('[data-narrator]')];
    if (!narrators.length) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canSpeak = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
    const played = new Set();
    const states = new Map();
    const voicesReady = canSpeak ? new Promise((resolve) => {
        const finish = (allowEmpty = false) => {
            if (window.speechSynthesis.getVoices().length || allowEmpty) {
                window.speechSynthesis.removeEventListener('voiceschanged', finish);
                resolve();
            }
        };

        window.speechSynthesis.addEventListener('voiceschanged', finish);
        finish();
        window.setTimeout(() => finish(true), 800);
    }) : Promise.resolve();

    function voicesForLocale(lang) {
        const requested = lang.toLowerCase();
        const language = requested.split('-')[0];

        return window.speechSynthesis.getVoices()
            .filter((voice) => voice.lang?.toLowerCase().startsWith(language))
            .sort((first, second) => {
                const firstExact = first.lang.toLowerCase() === requested ? 1 : 0;
                const secondExact = second.lang.toLowerCase() === requested ? 1 : 0;

                return secondExact - firstExact;
            });
    }

    function maleVoiceName(voice) {
        return /\b(otoya|ichiro|takumi|male|man)\b/i.test(`${voice.name} ${voice.voiceURI || ''}`);
    }

    function chooseVoice(element) {
        const lang = element.dataset.lang;
        const voices = voicesForLocale(lang);
        if (!voices.length) return null;

        const femaleNames = /\b(kyoko|ayumi|haruka|nanami|mizuki|yuna|sachiko|eiko|akari|hina|mai|female|woman|girl)\b/i;
        const maleNames = /\b(otoya|ichiro|takumi|male|man)\b/i;
        const preferredFemaleVoice = voices.find((voice) => femaleNames.test(`${voice.name} ${voice.voiceURI || ''}`));

        if (element.dataset.voicePreference === 'female' && preferredFemaleVoice) {
            return preferredFemaleVoice;
        }

        return voices
            .map((voice, index) => {
                const name = `${voice.name} ${voice.voiceURI || ''}`;
                let score = (voice.lang.toLowerCase() === lang.toLowerCase() ? 30 : 0) + (voice.localService ? 4 : 0) - index;

                if (element.dataset.voicePreference === 'female') {
                    if (femaleNames.test(name)) score += 100;
                    if (maleNames.test(name)) score -= 100;
                }

                return { voice, score };
            })
            .sort((first, second) => second.score - first.score)[0].voice;
    }

    const fullText = (element) => element.querySelector('[data-narrator-text]').dataset.text;
    const isVisible = (element) => element.getClientRects().length > 0;

    /**
     * The line is split into the part already "said" and the part still to come.
     * The unsaid part stays in the layout (just transparent), so the bubble
     * keeps its final size instead of growing and pushing things around.
     */
    function textParts(element) {
        const target = element.querySelector('[data-narrator-text]');
        let typed = target.querySelector('.narrator-typed');
        let rest = target.querySelector('.narrator-rest');

        if (!typed || !rest) {
            target.textContent = '';
            typed = document.createElement('span');
            typed.className = 'narrator-typed';
            rest = document.createElement('span');
            rest.className = 'narrator-rest';
            target.append(typed, rest);
        }

        return { typed, rest };
    }

    function setProgress(element, characters, count) {
        const { typed, rest } = textParts(element);
        typed.textContent = characters.slice(0, count).join('');
        rest.textContent = characters.slice(count).join('');
    }

    function reveal(element, { finished = true } = {}) {
        const state = states.get(element);
        if (state?.timer) window.clearTimeout(state.timer);
        if (state) state.timer = null;

        setProgress(element, [...fullText(element)], Infinity);
        if (finished && !state?.speaking) element.classList.remove('is-speaking');
    }

    function stopSpeech(element) {
        const state = states.get(element);
        if (!state?.speaking) return;

        state.speaking = false;
        if (canSpeak) window.speechSynthesis.cancel();
        const button = element.querySelector('[data-narrator-listen]');
        button.textContent = element.dataset.listenLabel;
        if (!state.timer) element.classList.remove('is-speaking');
    }

    function halt(element) {
        stopSpeech(element);
        reveal(element);
    }

    function type(element) {
        const characters = [...fullText(element)];
        let index = 0;

        element.classList.add('is-speaking');
        setProgress(element, characters, 0);

        const step = () => {
            index += 1;
            setProgress(element, characters, index);

            const state = states.get(element);
            if (index >= characters.length) {
                state.timer = null;
                if (!state.speaking) element.classList.remove('is-speaking');
                return;
            }

            const pause = /[.!?。！？]/.test(characters[index - 1]) ? 130 : /[,、]/.test(characters[index - 1]) ? 65 : 15;
            state.timer = window.setTimeout(step, pause);
        };

        states.get(element).timer = window.setTimeout(step, 180);
    }

    async function speak(element) {
        const state = states.get(element);
        const button = element.querySelector('[data-narrator-listen]');

        if (state.speaking) {
            stopSpeech(element);
            return;
        }

        window.speechSynthesis.cancel();
        state.speaking = true;
        button.textContent = element.dataset.stopLabel;
        element.classList.add('is-speaking');

        await voicesReady;
        if (!state.speaking) return;

        const utterance = new SpeechSynthesisUtterance(fullText(element));
        utterance.lang = element.dataset.lang;
        const voice = chooseVoice(element);
        if (voice) utterance.voice = voice;
        if (element.dataset.lang === 'ja-JP' && (!voice || maleVoiceName(voice))) utterance.pitch = 1.12;
        utterance.onend = utterance.onerror = () => {
            if (state.speaking) stopSpeech(element);
        };

        window.speechSynthesis.speak(utterance);
    }

    function refresh() {
        narrators.forEach((element) => {
            const visible = isVisible(element);
            const state = states.get(element);

            if (!visible) {
                if (state.active) {
                    halt(element);
                    state.active = false;
                }
                return;
            }

            if (state.active) return;
            state.active = true;

            const key = element.dataset.key;
            if (reducedMotion || played.has(key)) {
                reveal(element);
            } else {
                played.add(key);
                type(element);
            }
        });
    }

    narrators.forEach((element) => {
        states.set(element, { timer: null, speaking: false, active: false });

        const listen = element.querySelector('[data-narrator-listen]');
        listen.hidden = !canSpeak;
        listen.addEventListener('click', () => speak(element));
        element.querySelector('[data-narrator-skip]').addEventListener('click', () => halt(element));
    });

    // A browser can support speech synthesis in general but still ship with
    // no voice for this guest's language — reading the text with whatever
    // default voice it falls back to just produces the wrong language, so
    // hide the control rather than mislead the guest.
    if (canSpeak) {
        voicesReady.then(() => {
            narrators.forEach((element) => {
                if (!voicesForLocale(element.dataset.lang).length) {
                    element.querySelector('[data-narrator-listen]').hidden = true;
                }
            });
        });
    }

    window.addEventListener('hashchange', () => window.requestAnimationFrame(refresh));
    window.addEventListener('pagehide', () => { if (canSpeak) window.speechSynthesis.cancel(); });
    window.requestAnimationFrame(refresh);
}

document.addEventListener('DOMContentLoaded', initNarrators);
