#!/usr/bin/env node
/**
 * UI convention lint. Node built-ins only.
 *
 * Scans the admin and the shared UI kit (see SCAN_PATHS) for: color literals
 * (hex/rgb/hsl/oklch), Tailwind palette colors (bg-amber-950, text-stone-500,
 * bg-white, ...), arbitrary colors (bg-[#...]), inline style colors, CDN
 * Alpine or a second Alpine.start(), and color class names built from
 * variables. Colors must come from the semantic tokens in
 * resources/css/theme.css.
 *
 * The guest-facing stage (resources/css/presentation.css and the cafe-stage
 * views) is an illustrated scene with its own art direction and is not
 * scanned. This is a source lint, not proof that the UI is accessible.
 *
 * Usage: node scripts/check-ui-conventions.mjs [--root <dir>]
 */
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const args = process.argv.slice(2);
const rootIndex = args.indexOf('--root');
const ROOT = rootIndex !== -1 ? args[rootIndex + 1] : fileURLToPath(new URL('..', import.meta.url));

// Directories (or single files) under governance, relative to the project root.
const SCAN_PATHS = [
    'resources/views/admin',
    'resources/views/auth',
    'resources/views/components/ui',
    'resources/views/components/layouts',
    'resources/views/components/navigation',
    'resources/js/ui',
    'resources/js/shared',
    'resources/js/app.js',
    'resources/css/base.css',
    'app/Support/Ui',
];
const EXTENSIONS = ['.php', '.js', '.mjs', '.css'];
const BOOTSTRAP_FILE = 'resources/js/app.js';

const COLOR_NAMES = [
    'slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald',
    'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
];
const COLOR_UTILITIES = [
    'bg', 'text', 'border', 'border-[trblxy]', 'ring', 'ring-offset', 'outline', 'divide', 'fill', 'stroke', 'from',
    'via', 'to', 'placeholder', 'caret', 'accent', 'decoration', 'shadow',
];
const PREFIX = `(?<![\\w-])(?:[a-z0-9-]+:)*(?:${COLOR_UTILITIES.join('|')})`;

const RULES = [
    {
        id: 'color-literal',
        message: 'Color literal outside theme.css; use a semantic token.',
        pattern: /#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{3,4})(?![\w-])|\b(?:rgba?|hsla?|oklch|oklab|lab|lch)\(/g,
    },
    {
        id: 'palette-class',
        message: 'Tailwind palette color; use a semantic token from theme.css.',
        pattern: new RegExp(`${PREFIX}-(?:${COLOR_NAMES.join('|')})-(?:50|[1-9]00|950)(?:\\/\\d+)?(?![\\w-])`, 'g'),
    },
    {
        id: 'black-white-class',
        message: 'bg-white/text-black style classes are not tokens; use surface/text tokens.',
        pattern: new RegExp(`${PREFIX}-(?:white|black)(?:\\/\\d+)?(?![\\w-])`, 'g'),
    },
    {
        id: 'arbitrary-color',
        message: 'Arbitrary color value; add a token to theme.css instead.',
        pattern: new RegExp(`${PREFIX}-\\[(?:#|rgb|hsl|oklch|color:|var\\(--color)`, 'g'),
    },
    {
        id: 'inline-style-color',
        message: 'Inline style color/background literal; use classes with tokens.',
        pattern: /style\s*=\s*["'][^"']*\b(?:color|background(?:-color)?|border-color|fill|stroke)\s*:/gi,
    },
    {
        id: 'alpine-cdn',
        message: 'Alpine must come from the Vite bundle, not a CDN.',
        pattern: /<script[^>]+src=["'][^"']*(?:alpinejs|cdn\.jsdelivr\.net\/npm\/alpinejs|unpkg\.com\/alpinejs)/gi,
    },
    {
        id: 'dynamic-color-class',
        message: 'Color class built from a variable; map whitelisted tones to classes instead.',
        pattern: /(?:['"`]|\s)(?:bg|text|border|ring|fill|stroke)-(?:\$\{|['"]\s*\+|\{\{\s*\$)/g,
    },
];

// Specific, reasoned exceptions: [path, rule id, substring of the offending line].
const ALLOW = [];

function walk(path) {
    let stat;

    try {
        stat = statSync(path);
    } catch {
        return [];
    }

    if (!stat.isDirectory()) {
        return EXTENSIONS.some((ext) => path.endsWith(ext)) ? [path] : [];
    }

    return readdirSync(path).flatMap((name) => (['vendor', 'node_modules', 'build'].includes(name) ? [] : walk(join(path, name))));
}

function allowed(file, ruleId, line) {
    return ALLOW.some(([path, id, needle]) => path === file && id === ruleId && line.includes(needle));
}

export function checkSource(file, source) {
    const problems = [];
    const lines = source.split(/\r?\n/);

    lines.forEach((line, index) => {
        for (const rule of RULES) {
            rule.pattern.lastIndex = 0;
            if (rule.pattern.test(line) && !allowed(file, rule.id, line)) {
                problems.push({ file, line: index + 1, rule: rule.id, message: rule.message, text: line.trim().slice(0, 160) });
            }
        }

        const isComment = /^\s*(?:\*|\/\/|\/\*|\{\{--)/.test(line);
        if (file !== BOOTSTRAP_FILE && !isComment && /\bAlpine\.start\s*\(/.test(line)) {
            problems.push({ file, line: index + 1, rule: 'alpine-start', message: `Alpine.start() is only allowed in ${BOOTSTRAP_FILE}.`, text: line.trim().slice(0, 160) });
        }
    });

    return problems;
}

function main() {
    const files = [...new Set(SCAN_PATHS.flatMap((path) => walk(join(ROOT, path))))]
        .map((full) => relative(ROOT, full).split(sep).join('/'));

    const problems = files.flatMap((file) => checkSource(file, readFileSync(join(ROOT, file), 'utf8')));

    for (const problem of problems) {
        console.error(`${problem.file}:${problem.line} [${problem.rule}] ${problem.message}\n    ${problem.text}`);
    }

    if (problems.length > 0) {
        console.error(`\nlint:ui found ${problems.length} problem(s) in ${files.length} files.`);
        process.exit(1);
    }

    console.log(`lint:ui passed (${files.length} files).`);
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
    main();
}
