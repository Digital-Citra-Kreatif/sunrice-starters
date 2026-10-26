/*
 * The real-estate starter's script (packages/real-estate/resources/js/enhance.js, built into its public/lumina), with tiny fakes
 * instead of a browser.
 */
import assert from 'node:assert/strict';
import { test } from 'node:test';
// The starter's script is plain JavaScript (tests/Frontend isn't type-checked).
import { dropEmptyFields, setPageInert, submitOnPointerChange } from '../../packages/real-estate/resources/js/enhance.js';
import { needsFullLoad, shouldPrevent, withoutStyles } from '../../packages/real-estate/resources/js/transitions.js';

type Listener = (event: any) => void;

function fakeForm() {
    const listeners: Record<string, Listener[]> = {};
    let submits = 0;
    const form = {
        addEventListener: (type: string, listener: Listener) => { (listeners[type] ??= []).push(listener); },
        requestSubmit: () => { submits++; },
    };
    const fire = (type: string, event: object = {}) => listeners[type]?.forEach((listener) => listener(event));

    return { form, fire, submits: () => submits };
}

const select = { tagName: 'SELECT' };

test('filter selects never submit while a keyboard user arrows through them', () => {
    const { form, fire, submits } = fakeForm();
    submitOnPointerChange(form);

    fire('keydown');
    fire('change', { target: select });
    fire('change', { target: select });
    assert.equal(submits(), 0);
});

test('filter selects submit when changed with the mouse or touch, and only selects do', () => {
    const { form, fire, submits } = fakeForm();
    submitOnPointerChange(form);

    fire('pointerdown');
    fire('change', { target: select });
    assert.equal(submits(), 1);
    fire('change', { target: { tagName: 'INPUT' } });
    assert.equal(submits(), 1);
    // Back to the keyboard: no more submits on change.
    fire('keydown');
    fire('change', { target: select });
    assert.equal(submits(), 1);
});

test('a GET form leaves its empty fields out of the address', () => {
    const { form, fire } = fakeForm();
    dropEmptyFields(form);
    const formData = new FormData();
    formData.append('q', '');
    formData.append('listing_type', 'for-sale');
    formData.append('property_type', '');
    formData.append('sort', 'newest');

    fire('formdata', { formData });
    assert.deepEqual([...formData.entries()], [['listing_type', 'for-sale'], ['sort', 'newest']]);
});

test('the open mobile menu makes the rest of the page inert, and closing it gives it back', () => {
    const element = (name: string) => {
        const attributes = new Map<string, string>();
        return {
            name,
            attributes,
            contains: (other: unknown) => other === header,
            setAttribute: (key: string, value: string) => attributes.set(key, value),
            removeAttribute: (key: string) => attributes.delete(key),
        };
    };
    const skip = element('skip'); const main = element('main'); const footer = element('footer');
    const header: any = { ...element('header') };
    header.contains = () => false;
    header.ownerDocument = { body: { children: [skip, header, main, footer] } };
    skip.contains = main.contains = footer.contains = () => false;

    setPageInert(header, true);
    assert.deepEqual([skip, main, footer].map((el) => el.attributes.has('inert')), [true, true, true]);
    assert.equal(header.attributes.has('inert'), false);

    setPageInert(header, false);
    assert.deepEqual([skip, main, footer].map((el) => el.attributes.has('inert')), [false, false, false]);
});

// Page transitions (transitions.js): which links swap the next page in, and which load it in full.
function link(attributes: Record<string, string>) {
    return {
        getAttribute: (name: string) => attributes[name] ?? null,
        hasAttribute: (name: string) => name in attributes,
    };
}

const page = 'https://lumina.test/properties?listing_type=for-sale';

test('links to the site\'s pages are swapped in', () => {
    for (const href of ['/properties/villa-samudra', 'https://lumina.test/journal', '/id/properti?page=2', '/', 'agents']) {
        assert.equal(shouldPrevent(link({ href }), page), false, href);
    }
    assert.equal(shouldPrevent(link({ href: '/about', target: '_self' }), page), false);
});

test('language switcher links, downloads, other windows and links that ask for it load in full', () => {
    assert.equal(shouldPrevent(link({ href: '/id', hreflang: 'id' }), page), true);
    assert.equal(shouldPrevent(link({ href: '/about', 'data-barba-prevent': '' }), page), true);
    assert.equal(shouldPrevent(link({ href: '/about', download: '' }), page), true);
    assert.equal(shouldPrevent(link({ href: '/about', target: '_blank' }), page), true);
});

test('other origins and protocols, anchors, files and the admin panel load in full', () => {
    for (const href of [
        'https://example.com/listing', 'http://lumina.test/about', 'https://lumina.test:8443/about',
        'mailto:hello@example.com', 'tel:+622155501000', '#main', '/about#team', '',
        '/storage/media/villa.jpg', '/brochure.pdf', '/sitemap.xml', '/admin', '/admin/entries', '/storage',
    ]) {
        assert.equal(shouldPrevent(link({ href }), page), true, href);
    }
    assert.equal(shouldPrevent(link({}), page), true);
    // Only the path itself, not a page that starts with the same letters.
    assert.equal(shouldPrevent(link({ href: '/administration-fees' }), page), false);
    assert.equal(shouldPrevent(link({ href: '/journal/notes.html' }), page), false);
    assert.equal(shouldPrevent(link({ href: '/panel/x' }), page, ['/panel/']), true);
});

test('a next page with code of its own or the draft banner loads in full', () => {
    const element = (matches: string[]) => ({ querySelector: (selector: string) => (matches.some((m) => selector.includes(m)) ? {} : null) });
    const plain = element([]);
    assert.equal(needsFullLoad(plain, plain), false);
    assert.equal(needsFullLoad(element(['script:not']), plain), true);
    assert.equal(needsFullLoad(element(['style']), plain), true);
    assert.equal(needsFullLoad(plain, element(['data-sunrice-draft-banner'])), true);
});

test('a fetched page is parsed without its <style> elements (another request\'s nonce)', () => {
    const html = '<head><style nonce="a">@font-face{}</style><title>T</title></head><body><STYLE>p{}</STYLE ><main>x</main></body>';
    assert.equal(withoutStyles(html), '<head><title>T</title></head><body><main>x</main></body>');
});
