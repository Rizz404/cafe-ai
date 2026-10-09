#!/usr/bin/env node
/**
 * Cafe UI sound generator. Node built-ins only.
 *
 * Synthesises the three guest-stage sounds and writes them as 16-bit mono WAV
 * files that resources/js/sound.js imports through Vite:
 *
 *   enter.wav     the little brass bells on a cafe door, shaken as it opens
 *   incoming.wav  the "ting" of a counter service bell (the barista replied)
 *   sent.wav      a spoon tapping a ceramic cup (the guest sent a message)
 *
 * The output is deterministic (seeded noise), so re-running only changes the
 * files when this script changes. To change a sound, edit it below and run
 * `npm run sounds`.
 *
 * Usage: node scripts/generate-sounds.mjs [--out <dir>]
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const SAMPLE_RATE = 44100;
const ATTACK_SECONDS = 0.0015;

const args = process.argv.slice(2);
const outIndex = args.indexOf('--out');
const OUT_DIR = outIndex !== -1
    ? args[outIndex + 1]
    : fileURLToPath(new URL('../resources/audio', import.meta.url));

/** Small seeded PRNG (mulberry32) so the noise in each sound is repeatable. */
function createRandom(seed) {
    let state = seed >>> 0;

    return () => {
        state = (state + 0x6d2b79f5) >>> 0;
        let value = Math.imul(state ^ (state >>> 15), 1 | state);
        value = (value + Math.imul(value ^ (value >>> 7), 61 | value)) ^ value;

        return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
    };
}

function createTrack(seconds) {
    return new Float32Array(Math.ceil(seconds * SAMPLE_RATE));
}

/**
 * One struck piece of metal or ceramic: a set of inharmonic partials, each
 * with its own decay time, so the highs vanish first and the body rings on.
 */
function addStrike(track, random, { start, frequency, gain, partials }) {
    const first = Math.round(start * SAMPLE_RATE);

    for (const { ratio, amp, decay } of partials) {
        const partialFrequency = frequency * ratio;
        if (partialFrequency > 16000) continue;

        const omega = (2 * Math.PI * partialFrequency) / SAMPLE_RATE;
        const phase = random() * 2 * Math.PI;

        for (let i = first; i < track.length; i++) {
            const t = (i - first) / SAMPLE_RATE;
            const envelope = Math.min(1, t / ATTACK_SECONDS) * Math.exp(-t / decay);
            if (t > ATTACK_SECONDS && envelope < 0.0004) break;

            track[i] += gain * amp * envelope * Math.sin(omega * (i - first) + phase);
        }
    }
}

/** A short burst of low-passed noise: the "thock" of wood or the tick of china. */
function addClick(track, random, { start, gain, decay, brightness }) {
    const first = Math.round(start * SAMPLE_RATE);
    let smoothed = 0;

    for (let i = first; i < track.length; i++) {
        const t = (i - first) / SAMPLE_RATE;
        const envelope = Math.exp(-t / decay);
        if (envelope < 0.001) break;

        smoothed += brightness * ((random() * 2 - 1) - smoothed);
        track[i] += gain * envelope * smoothed;
    }
}

/** A small room around the sound: four combs and two all-passes, kept subtle. */
function addRoom(track, wet) {
    const combs = [[0.0297, 0.8], [0.0371, 0.78], [0.0411, 0.76], [0.0437, 0.74]];
    const allPasses = [0.005, 0.0017];
    const mix = new Float32Array(track.length);

    for (const [delaySeconds, feedback] of combs) {
        const delay = Math.round(delaySeconds * SAMPLE_RATE);
        const line = new Float32Array(track.length);

        for (let i = 0; i < track.length; i++) {
            line[i] = track[i] + (i >= delay ? line[i - delay] * feedback : 0);
            mix[i] += (line[i] * (1 - feedback)) / combs.length;
        }
    }

    for (const delaySeconds of allPasses) {
        const delay = Math.round(delaySeconds * SAMPLE_RATE);
        const source = Float32Array.from(mix);

        for (let i = 0; i < mix.length; i++) {
            const delayedIn = i >= delay ? source[i - delay] : 0;
            const delayedOut = i >= delay ? mix[i - delay] : 0;
            mix[i] = -0.7 * source[i] + delayedIn + 0.7 * delayedOut;
        }
    }

    for (let i = 0; i < track.length; i++) {
        track[i] += wet * mix[i];
    }
}

/** Scale to `peak`, ease the last `fadeSeconds` out so nothing ends on a click. */
function finish(track, peak, fadeSeconds) {
    let loudest = 0;
    for (const sample of track) loudest = Math.max(loudest, Math.abs(sample));

    const scale = loudest > 0 ? peak / loudest : 1;
    const fadeLength = Math.round(fadeSeconds * SAMPLE_RATE);

    for (let i = 0; i < track.length; i++) {
        const fromEnd = track.length - 1 - i;
        const fade = fromEnd < fadeLength ? fromEnd / fadeLength : 1;
        track[i] *= scale * fade;
    }

    return track;
}

function encodeWav(track) {
    const dataSize = track.length * 2;
    const buffer = Buffer.alloc(44 + dataSize);

    buffer.write('RIFF', 0);
    buffer.writeUInt32LE(36 + dataSize, 4);
    buffer.write('WAVE', 8);
    buffer.write('fmt ', 12);
    buffer.writeUInt32LE(16, 16);
    buffer.writeUInt16LE(1, 20); // PCM
    buffer.writeUInt16LE(1, 22); // mono
    buffer.writeUInt32LE(SAMPLE_RATE, 24);
    buffer.writeUInt32LE(SAMPLE_RATE * 2, 28);
    buffer.writeUInt16LE(2, 32);
    buffer.writeUInt16LE(16, 34);
    buffer.write('data', 36);
    buffer.writeUInt32LE(dataSize, 40);

    for (let i = 0; i < track.length; i++) {
        const sample = Math.max(-1, Math.min(1, track[i]));
        buffer.writeInt16LE(Math.round(sample * 32767), 44 + i * 2);
    }

    return buffer;
}

/** Small brass bells: bright, quick to fade, with a few inharmonic overtones. */
const DOOR_BELL = [
    { ratio: 1, amp: 1, decay: 0.34 },
    { ratio: 2.32, amp: 0.5, decay: 0.18 },
    { ratio: 4.25, amp: 0.25, decay: 0.1 },
    { ratio: 6.63, amp: 0.12, decay: 0.06 },
];

/** A counter service bell: one clear note with a bright, short-lived crown. */
const SERVICE_BELL = [
    { ratio: 1, amp: 1, decay: 0.5 },
    { ratio: 2, amp: 0.4, decay: 0.32 },
    { ratio: 2.76, amp: 0.34, decay: 0.2 },
    { ratio: 5.4, amp: 0.15, decay: 0.11 },
    { ratio: 8.93, amp: 0.07, decay: 0.05 },
];

/** Glazed china: a bright ring that is gone almost at once. */
const CERAMIC = [
    { ratio: 1, amp: 1, decay: 0.045 },
    { ratio: 2.76, amp: 0.5, decay: 0.026 },
    { ratio: 5.4, amp: 0.22, decay: 0.014 },
];

const SOUNDS = {
    /** The door opens: a low bell for body, then the small bells shaken loose. */
    enter() {
        const random = createRandom(11);
        const track = createTrack(2.4);

        addClick(track, random, { start: 0, gain: 0.5, decay: 0.012, brightness: 0.12 });
        addStrike(track, random, { start: 0.005, frequency: 1568, gain: 0.5, partials: DOOR_BELL.map((p) => ({ ...p, decay: p.decay * 2.2 })) });

        const shake = [
            [0.02, 2637, 0.9], [0.075, 3136, 0.7], [0.135, 2349, 0.8], [0.215, 3520, 0.55],
            [0.3, 2794, 0.5], [0.41, 3136, 0.34], [0.55, 2637, 0.24],
        ];
        for (const [start, frequency, gain] of shake) {
            addStrike(track, random, { start, frequency, gain, partials: DOOR_BELL });
        }

        addRoom(track, 0.55);

        return finish(track, 0.7, 0.12);
    },

    /** The barista answered: a single clear "ting" with a softer echo of it. */
    incoming() {
        const random = createRandom(23);
        const track = createTrack(1.5);

        addStrike(track, random, { start: 0, frequency: 1760, gain: 1, partials: SERVICE_BELL });
        addStrike(track, random, { start: 0.17, frequency: 1760, gain: 0.3, partials: SERVICE_BELL });

        addRoom(track, 0.4);

        return finish(track, 0.6, 0.1);
    },

    /** A message went out: a spoon touching the rim of a cup. */
    sent() {
        const random = createRandom(37);
        const track = createTrack(0.32);

        addClick(track, random, { start: 0, gain: 0.35, decay: 0.006, brightness: 0.7 });
        addStrike(track, random, { start: 0, frequency: 1900, gain: 1, partials: CERAMIC });

        addRoom(track, 0.15);

        return finish(track, 0.38, 0.05);
    },
};

mkdirSync(OUT_DIR, { recursive: true });

for (const [name, render] of Object.entries(SOUNDS)) {
    const track = render();
    const file = join(OUT_DIR, `${name}.wav`);

    writeFileSync(file, encodeWav(track));
    console.log(`${name}.wav  ${(track.length / SAMPLE_RATE).toFixed(2)}s  ${Math.round((track.length * 2 + 44) / 1024)} KB`);
}
