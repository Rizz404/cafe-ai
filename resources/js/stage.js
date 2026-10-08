/**
 * Full-screen stage chrome shared by the opening screen and the cafe:
 * hides the "preparing" loader once the hero image is ready, and eases the
 * scene out before following an "enter" link so moving between screens feels
 * like walking through the cafe rather than loading a page.
 *
 * A link may carry `data-topic`: the question the guest picked from the menu.
 * It is handed to the AI barista, which raises it once the next scene is up
 * (or straight away when the guest is already in that scene).
 */
const TOPIC_KEY = 'barista_pending_topic';

function initStage() {
    const stage = document.querySelector('.stage');
    if (!stage) return;

    const loader = stage.querySelector('[data-stage-loader]');
    const image = stage.querySelector('.stage-image');
    const tour = stage.querySelector('[data-stage-tour]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const showScene = () => loader?.classList.add('is-ready');

    if (!image || image.complete) {
        showScene();
    } else {
        image.addEventListener('load', showScene, { once: true });
        image.addEventListener('error', showScene, { once: true });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            leaving = false;
            stage.classList.remove('is-leaving');
            tour?.classList.remove('is-visible');
        }
    });

    let leaving = false;

    /** Ease the stage out, then walk the guest over to `href`. */
    function leave(href, line = null, topic = null) {
        if (leaving) return;
        leaving = true;

        try {
            if (topic) sessionStorage.setItem(TOPIC_KEY, topic);
            else sessionStorage.removeItem(TOPIC_KEY);
        } catch { /* private mode: the scene still changes, only the topic is dropped */ }

        // The barista says where she is taking the guest, then the scene
        // walks forward into the next one.
        if (line && tour) {
            tour.textContent = line;
            tour.classList.add('is-visible');
        }

        stage.classList.add('is-leaving');

        const chime = window.cafeSound?.isEnabled() ?? false;
        if (chime) window.cafeSound.play('enter');

        const hold = reducedMotion ? 0 : line ? 820 : chime ? 560 : 320;
        window.setTimeout(() => { window.location.href = href; }, hold);
    }

    document.querySelectorAll('[data-stage-exit]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const modified = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
            if (event.defaultPrevented || modified || link.target === '_blank') return;

            event.preventDefault();

            // Already in this scene: no walk needed, the barista just takes
            // the topic up where the guest is standing.
            const samePlace = link.pathname === window.location.pathname;
            if (samePlace && link.dataset.topic) {
                window.dispatchEvent(new CustomEvent('barista:ask', { detail: { message: link.dataset.topic } }));
                return;
            }
            if (samePlace) return;

            leave(link.href, link.dataset.tourLine, link.dataset.topic);
        });
    });

    window.cafeStage = { leave };
}

/** The topic the guest picked on the previous scene, if any (read once). */
window.takePendingBaristaTopic = () => {
    try {
        const topic = sessionStorage.getItem(TOPIC_KEY);
        sessionStorage.removeItem(TOPIC_KEY);
        return topic;
    } catch {
        return null;
    }
};

document.addEventListener('DOMContentLoaded', initStage);
