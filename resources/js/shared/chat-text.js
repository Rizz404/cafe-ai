/**
 * Turns the model's reply into the small amount of markup a chat bubble needs:
 * paragraphs, line breaks, bullet and numbered lists, bold and italic. The text
 * is escaped first, so nothing the model writes can become real HTML, and
 * markdown the bubble cannot show (headings, code, rules) is cleaned up
 * instead of leaking through as stray symbols.
 */
import { escapeHtml } from './dom.js';

const BULLET = /^\s*[-*•]\s+(.*)$/;
const NUMBERED = /^\s*\d+[.)]\s+(.*)$/;
const HEADING = /^\s{0,3}#{1,6}\s+(.*?)\s*#*\s*$/;
const RULE = /^\s*([-*_])(\s*\1){2,}\s*$/;
const FENCE = /^\s*(```|~~~)/;

function inline(text) {
    return escapeHtml(text)
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\*\*(.+?)\*\*|__(.+?)__/g, (_, star, underscore) => `<strong>${star ?? underscore}</strong>`)
        .replace(/(^|[^*\w])\*(?!\s)([^*]+?)(?<!\s)\*(?![*\w])/g, '$1<em>$2</em>');
}

export function renderChatText(text) {
    const html = [];
    let paragraph = [];
    let list = null;

    const flushParagraph = () => {
        if (paragraph.length) html.push(`<p>${paragraph.map(inline).join('<br>')}</p>`);
        paragraph = [];
    };

    const flushList = () => {
        if (list) html.push(`<${list.tag}>${list.items.map((item) => `<li>${inline(item)}</li>`).join('')}</${list.tag}>`);
        list = null;
    };

    const addItem = (tag, item) => {
        flushParagraph();
        if (list && list.tag !== tag) flushList();
        list ??= { tag, items: [] };
        list.items.push(item);
    };

    String(text ?? '').replace(/\r\n?/g, '\n').split('\n').forEach((line) => {
        if (FENCE.test(line)) return;

        if (line.trim() === '' || RULE.test(line)) {
            flushParagraph();
            flushList();
            return;
        }

        const bullet = line.match(BULLET);
        const numbered = line.match(NUMBERED);

        if (bullet) return addItem('ul', bullet[1]);
        if (numbered) return addItem('ol', numbered[1]);

        flushList();
        const heading = line.match(HEADING);
        paragraph.push(heading ? `**${heading[1]}**` : line);
    });

    flushParagraph();
    flushList();

    return html.join('');
}
