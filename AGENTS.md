# Working on the Sunrice starters

Each folder of `packages/` is one starter site, published as its own Composer package (`sunrice/starter-<name>`, namespace `Sunrice\Starters\<Name>\`). The starter kit's guide (`sunrice/packages/starter-kit/docs/starters.md` in the checkout) describes what a starter is and how to write a good one: follow it.

## A starter package

```
packages/<name>/
├── composer.json        sunrice/starter-<name>: requires sunrice/admin, sunrice/cms, sunrice/starter-kit
├── README.md            what it is, install, what lives where, the content model, keeping the package installed or not
├── src/                 its service provider (registers starter/ with Starters, and anything its templates need at run time)
├── starter/             the starter: starter.json, site.json, media/ + media.json, views/, public/, lang/
└── resources/           sources of its built assets (not copied by the installer), with a README on rebuilding
```

## Rules

- One change per branch and pull request, with a title that says what it does.
- Every starter installs on a fresh app with no files kept (`tests/StartersTest.php` checks it, and that every page of its sitemap answers, once, in the starter's layout: its site header carries `data-site-header`). Switch off the CMS's term and index pages in a collection (`collection_pages`) that the starter has no templates for. A starter with its own behaviour has its own tests in `tests/<Name>/`.
- Templates use only the CMS's helpers and components and the starter package's own classes: never an app's (`App\…`).
- Built assets live under `public/<folder of its own>/` with their own Vite manifest (the real-estate starter's `Assets::use()`), never `public/build`, so the app's own front end can't clash.
- The starter's site words are in `lang/<locale>/site.php`, with the same keys in every language (a test checks).
- Sample content: no real people's data, example addresses (`example.com`, `.test`), images light enough to keep the package small.
- Packages use only the panel's, the CMS's and the starter kit's `@api` classes.
- Every PHP file starts `declare(strict_types=1);`. Larastan runs at `level: max`.
- Before opening a PR: `composer test`, `composer lint`, `composer analyse`, and `npm run test:frontend` (node:test, `tests/Frontend`) when a starter's script changed.
- Don't commit `vendor/`, `composer.lock`, `.env` or the `sunrice/` checkout.

## Commands

```bash
composer test      # Pest
composer lint      # Pint (test mode)
composer format    # Pint (fix)
composer analyse   # Larastan
npm run test:frontend   # the starters' scripts (node:test)
```
