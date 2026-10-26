// Page transitions (Barba.js, see app.js): the rules and the page swap, kept free of globals so the rules can be
// tested without a browser (tests/Frontend).

/**
 * Paths that always load in full: the admin panel (change it here if the panel lives under another path) and the
 * media files on the public disk.
 */
export const FULL_LOAD_PATHS = ['/admin', '/storage'];

// A file (/brochure.pdf, /sitemap.xml, /feed.rss), not a page.
const FILE = /\.(?!html?$)[a-z0-9]{2,5}$/i;

/**
 * Whether a link loads the next page in full instead of swapping it in: `true` for links that ask for it
 * (`data-barba-prevent`), language switcher links (`hreflang`, the next page's header, footer and words are another
 * language's), downloads, other windows, `#` anchors and links with one, other origins and protocols (mailto:, tel:),
 * files and FULL_LOAD_PATHS. Barba already handles some of these itself; checking them here keeps the rules in one
 * place.
 *
 * @param {{ getAttribute(name: string): string | null, hasAttribute(name: string): boolean }} link
 * @param {string} base the address of the page shown
 */
export function shouldPrevent(link, base, fullLoadPaths = FULL_LOAD_PATHS) {
    if (['data-barba-prevent', 'download', 'hreflang'].some((name) => link.hasAttribute(name))) return true;
    const target = link.getAttribute('target');
    if (target && target !== '_self') return true;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#')) return true;

    let url;
    try {
        url = new URL(href, base);
    } catch {
        return true;
    }
    if (url.origin !== new URL(base).origin || url.hash !== '') return true;
    const path = url.pathname;

    return fullLoadPaths.some((prefix) => path === prefix || path.startsWith(`${prefix.replace(/\/$/, '')}/`)) || FILE.test(path);
}

// Code in the next page's content: swapped in, it wouldn't run (and an inline one's nonce is another request's).
const PAGE_CODE = 'script:not([type="application/ld+json"]), style, link[rel="stylesheet"]';

// The CMS's draft banner (signed-in editors), printed after <body>: outside the container, so it would stay stale.
export const DRAFT_BANNER = '[data-sunrice-draft-banner]';

/**
 * Whether the next page must load in full instead of being swapped in: its container has code of its own (a
 * captcha, a map), or the page has the CMS's draft banner.
 *
 * @param {Element} container the next page's container
 * @param {Document | Element} page the next page
 */
export function needsFullLoad(container, page) {
    return container.querySelector(PAGE_CODE) !== null || page.querySelector(DRAFT_BANNER) !== null;
}

/**
 * The fetched page without its <style> elements, for parsing it into a document (DOMParser): such a document has the
 * page's Content-Security-Policy, and the fetched page's nonce isn't this one's, so the browser would refuse each
 * one with an error in the console. Nothing is lost: the <head>'s styles are the same on every page, and a container
 * with styles of its own loads in full (needsFullLoad(), which looks at Barba's copy of the container).
 */
export function withoutStyles(html) {
    return html.replace(/<style\b[^>]*>[\s\S]*?<\/style\s*>/gi, '');
}

/** The <head> tags of the page shown that are swapped for the next page's: SEO tags and structured data. */
export const HEAD_TAGS = [
    'meta[name="description"]',
    'meta[name="robots"]',
    'link[rel="canonical"]',
    'link[rel="alternate"][hreflang]',
    'meta[property^="og:"]',
    'meta[property^="article:"]',
    'meta[name^="twitter:"]',
    'script[type="application/ld+json"]',
].join(', ');

/**
 * Brings what lives outside the container up to the next page (`next`, the fetched page parsed): the title, `<html
 * lang>`, the <head>'s SEO tags, the body classes (the container's `data-body-class`) and the children of each
 * `[data-swap]` element (the header's menus and language switcher, the footer), which keeps its own listeners and
 * state classes.
 *
 * @param {Document} doc the page shown
 * @param {Document} next the next page
 * @param {HTMLElement} container the next page's container
 */
export function syncDocument(doc, next, container) {
    doc.title = next.title;
    for (const name of ['lang', 'dir']) {
        const value = next.documentElement.getAttribute(name);
        if (value === null) doc.documentElement.removeAttribute(name);
        else doc.documentElement.setAttribute(name, value);
    }

    const old = [...doc.head.querySelectorAll(HEAD_TAGS)];
    const anchor = old[0] ?? null;
    for (const tag of next.head.querySelectorAll(HEAD_TAGS)) {
        doc.head.insertBefore(doc.importNode(tag, true), anchor);
    }
    old.forEach((tag) => tag.remove());

    doc.body.className = container.dataset.bodyClass ?? next.body.className;

    for (const element of doc.querySelectorAll('[data-swap]')) {
        const fresh = next.querySelector(`[data-swap="${CSS.escape(element.dataset.swap)}"]`);
        if (fresh) element.replaceChildren(...[...fresh.childNodes].map((node) => doc.importNode(node, true)));
    }
}
