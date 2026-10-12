/**
 * Plays the barista's reply aloud. The audio is made by the backend (text to
 * speech), fetched as one MP3 and played right away. Every failure is silent:
 * the reply is already on screen, so the guest only loses the voice.
 */
import { csrfToken } from './api.js';

/**
 * @param {{ url: string, onChange?: (speaking: boolean) => void }} options
 */
export function createSpeaker({ url, onChange = () => {} }) {
    let audio = null;
    let objectUrl = null;
    let request = 0;

    function release() {
        if (audio) {
            audio.onended = audio.onerror = null;
            audio.pause();
            audio = null;
        }

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        onChange(false);
    }

    /** Cuts off whatever is being said, or fetched, right now. */
    function stop() {
        request += 1;
        release();
    }

    /** @param {object} payload what the backend needs to find the line to speak */
    async function speak(payload) {
        stop();
        const mine = request;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'audio/mpeg',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            if (response.status !== 200) return;

            const blob = await response.blob();
            if (mine !== request) return;

            objectUrl = URL.createObjectURL(blob);
            audio = new Audio(objectUrl);
            audio.onended = audio.onerror = release;

            await audio.play();
            if (mine === request) onChange(true);
        } catch {
            if (mine === request) release();
        }
    }

    return { speak, stop };
}
