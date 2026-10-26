// Lumina Estates: progressive enhancement only. Every page works without this script.
// Barba's modern ES module build (the package's default for browsers is its larger UMD build, with old-browser code).
import barba from '@barba/core/dist/barba.modern.mjs';
import { dropEmptyFields, setPageInert, submitOnPointerChange } from './enhance.js';
import { DRAFT_BANNER, needsFullLoad, shouldPrevent, syncDocument, withoutStyles } from './transitions.js';

document.documentElement.classList.add('js');

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Header: solid once the page scrolls (over a hero it starts transparent). The header stays when pages are swapped
// (only its children are replaced, see transitions.js), so its listeners are set once and delegated.
const header = document.querySelector('[data-site-header]');
const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 24);
const setMenuOpen = (open) => {
    if (!header) return;
    header.classList.toggle('is-open', open);
    header.querySelector('[data-nav-toggle]')?.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
    setPageInert(header, open);
};
if (header) {
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
    header.addEventListener('click', (event) => {
        if (event.target.closest('[data-nav-toggle]')) setMenuOpen(!header.classList.contains('is-open'));
    });
    // The menu is for small screens: growing past the breakpoint closes it, so the page isn't left inert.
    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches && header.classList.contains('is-open')) setMenuOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && header.classList.contains('is-open')) {
            setMenuOpen(false);
            header.querySelector('[data-nav-toggle]')?.focus();
        }
    });
}

/**
 * The enhancements of a page's content: reveal on scroll, the gallery viewer, filter selects. `container` is the Barba
 * container (or the whole page without one); the `[data-swap]` parts outside it (the header's menus, the footer) are
 * replaced with each page too. Returns the function that undoes them when the page is swapped out.
 */
function enhance(container) {
    const roots = container === document.body ? [container] : [container, ...document.querySelectorAll('[data-swap]')];
    const all = (selector) => roots.flatMap((root) => [...root.querySelectorAll(selector)]);
    const controller = new AbortController();
    const on = { signal: controller.signal };
    const undo = [() => controller.abort()];

    // Reveal sections as they enter the viewport.
    const revealables = all('.reveal');
    if (!reduceMotion() && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            }
        }, { rootMargin: '0px 0px -8% 0px' });
        revealables.forEach((element) => observer.observe(element));
        undo.push(() => observer.disconnect());
    } else {
        revealables.forEach((element) => element.classList.add('is-visible'));
    }

    // Gallery viewer: a <dialog> with previous/next, keyboard arrows and swipe.
    for (const gallery of all('[data-gallery]')) {
        const dialog = document.getElementById(gallery.dataset.gallery);
        if (!(dialog instanceof HTMLDialogElement)) continue;
        const triggers = [...gallery.querySelectorAll('[data-index]')];
        const items = JSON.parse(dialog.dataset.items || '[]');
        const image = dialog.querySelector('img');
        const caption = dialog.querySelector('[data-caption]');
        const counter = dialog.querySelector('[data-counter]');
        let index = 0;
        let lastTrigger = null;

        const show = (next) => {
            index = (next + items.length) % items.length;
            const item = items[index];
            image.src = item.src;
            image.srcset = item.srcset || '';
            image.alt = item.alt || '';
            caption.textContent = item.alt || '';
            counter.textContent = `${index + 1} / ${items.length}`;
            // restart the fade
            image.style.animation = 'none';
            void image.offsetWidth;
            image.style.animation = '';
        };

        triggers.forEach((trigger) => trigger.addEventListener('click', () => {
            lastTrigger = trigger;
            show(Number(trigger.dataset.index));
            dialog.showModal();
        }, on));
        dialog.querySelector('[data-prev]')?.addEventListener('click', () => show(index - 1), on);
        dialog.querySelector('[data-next]')?.addEventListener('click', () => show(index + 1), on);
        dialog.querySelector('[data-close]')?.addEventListener('click', () => dialog.close(), on);
        dialog.addEventListener('close', () => lastTrigger?.focus(), on);
        dialog.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') show(index - 1);
            if (event.key === 'ArrowRight') show(index + 1);
        }, on);
        let startX = null;
        dialog.addEventListener('touchstart', (event) => { startX = event.touches[0].clientX; }, { ...on, passive: true });
        dialog.addEventListener('touchend', (event) => {
            if (startX === null) return;
            const delta = event.changedTouches[0].clientX - startX;
            if (Math.abs(delta) > 40) show(index + (delta < 0 ? 1 : -1));
            startX = null;
        }, on);
        undo.push(() => dialog.open && dialog.close());
    }

    // Filters: a select changed with the mouse or touch submits the form; keyboard users submit with the button.
    for (const form of all('[data-autosubmit]')) {
        submitOnPointerChange(form, on);
    }
    for (const form of all('form[method="get"]')) {
        dropEmptyFields(form, on);
    }

    return () => undo.forEach((fn) => fn());
}

let undoPage = enhance(document.querySelector('[data-barba="container"]') ?? document.body);

// Page transitions: with `data-barba="wrapper"` on <body> (the layout; remove it to turn them off), links to the
// site's pages fetch the next page and swap its <main data-barba="container"> in, with a short fade. Forms aren't
// touched (they submit normally), and shouldPrevent() lists the links that load in full.
const wrapper = document.querySelector('[data-barba="wrapper"]');
// Not under the CMS's draft banner (signed-in editors): it belongs to the page it's on.
if (wrapper && document.querySelector('[data-barba="container"]') && !document.querySelector(DRAFT_BANNER)) {
    const announcer = document.querySelector('[data-page-announcer]');
    const fade = (element, keyframes, options) => (reduceMotion() || !element.animate
        ? Promise.resolve()
        : element.animate(keyframes, options).finished.catch(() => {}));

    // Barba parses each fetched page for its <title> (barba.dom.toDocument()): without its <style> elements, see
    // withoutStyles().
    const toDocument = barba.dom.toDocument.bind(barba.dom);
    barba.dom.toDocument = (html) => toDocument(withoutStyles(html));

    barba.init({
        timeout: 5000,
        // No fetching on hover: only the page clicked is requested.
        prefetchIgnore: true,
        // A click while a page is being swapped in waits instead of reloading.
        preventRunning: true,
        prevent: ({ el }) => shouldPrevent(el, window.location.href),
        transitions: [{
            name: 'lumina-fade',
            leave: ({ current }) => fade(current.container, [{ opacity: 1 }, { opacity: 0, transform: 'translateY(-6px)' }], { duration: 180, easing: 'ease-in', fill: 'forwards' }),
            enter: ({ next }) => fade(next.container, [{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }], { duration: 260, easing: 'cubic-bezier(0.25, 1, 0.5, 1)' }),
        }],
    });

    barba.hooks.beforeLeave(() => {
        setMenuOpen(false);
        undoPage();
    });

    // The next page is in (still invisible): drop the old one, bring the rest of the page up to it, top of the page.
    barba.hooks.beforeEnter(({ current, next }) => {
        // beforeEnter and afterEnter also run once for the first page (no current one), which is already set up.
        if (!current.container) return;
        const page = barba.dom.toDocument(next.html);
        // Barba loads the page in full when a hook throws.
        if (needsFullLoad(next.container, page)) throw new Error('Lumina: this page loads in full.');
        current.container.remove();
        syncDocument(document, page, next.container);
        window.scrollTo(0, 0);
        updateHeader();
    });

    barba.hooks.afterEnter(({ current, next, trigger }) => {
        if (!current.container) return;
        undoPage = enhance(next.container);
        // For keyboard and screen reader users: focus the new content and say which page it is.
        next.container.focus({ preventScroll: true });
        if (announcer) announcer.textContent = document.title;
        // For the site's own code (Settings → Site code doesn't run again on swapped pages) and analytics.
        document.dispatchEvent(new CustomEvent('lumina:page', {
            detail: { url: next.url.href, namespace: next.namespace, container: next.container, trigger: typeof trigger === 'string' ? trigger : 'link' },
        }));
    });
}
