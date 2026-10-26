// Small accessibility helpers for app.js, kept free of globals so they can be tested without a browser.

// `options` is passed to addEventListener: `{ signal }` removes the listeners when a page is swapped out (app.js).

/**
 * Filters: submit when a select changes with the pointer (mouse or touch), never while a keyboard user arrows
 * through the options (WCAG 3.2.2: that would reload the page on the first arrow key). Keyboard users submit with
 * the button or Enter.
 */
export function submitOnPointerChange(form, options = {}) {
    let pointer = false;
    form.addEventListener('pointerdown', () => { pointer = true; }, options);
    form.addEventListener('keydown', () => { pointer = false; }, options);
    form.addEventListener('change', (event) => {
        if (pointer && event.target?.tagName === 'SELECT') form.requestSubmit();
    }, options);
}

/** Leaves empty fields out of a GET form's address (`?listing_type=for-sale`, not `?q=&listing_type=for-sale&…`). */
export function dropEmptyFields(form, options = {}) {
    form.addEventListener('formdata', (event) => {
        for (const [name, value] of [...event.formData.entries()]) {
            if (value === '') event.formData.delete(name);
        }
    }, options);
}

/**
 * While the full-screen mobile menu is open, the rest of the page is inert, so Tab stays in the header and screen
 * readers don't wander into the page behind it.
 */
export function setPageInert(header, inert) {
    for (const element of header.ownerDocument.body.children) {
        if (element === header || element.contains(header)) continue;
        if (inert) element.setAttribute('inert', '');
        else element.removeAttribute('inert');
    }
}
