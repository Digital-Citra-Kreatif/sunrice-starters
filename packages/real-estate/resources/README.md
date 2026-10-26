# The starter's front-end sources

`public/lumina` (copied into the app by `sunrice:starter real-estate`) holds the built CSS, script and fonts. These are their sources: Tailwind CSS v4 (tokens in `@theme`, components in `@layer components` of `css/app.css`), a small script that only enhances (`js/app.js`: gallery viewer, mobile menu, header over a hero, reveal on scroll, filter selects submitting themselves, page transitions with [`@barba/core`](https://barba.js.org) from `js/transitions.js`; every page works without it) and fonts self-hosted at build time from the `@fontsource` packages (Cormorant Garamond and Manrope, latin woff2).

The templates load them with `Sunrice\Starters\RealEstate\Assets::use()` before `@fonts` and `@vite`: the build lives in `public/lumina` with its own manifest, apart from the app's `public/build`.

Tailwind reads classes from the templates only (`source(none)` and `@source '../views'` in `css/app.css`): `data-barba="container"` there isn't Tailwind's `.container`, hence `@source not inline('container')`.

## Page transitions

`js/app.js` sets them up with Barba (the starter's README says what they do, how to turn them off and the `lumina:page` event); `js/transitions.js` has the rules (`shouldPrevent()`: which links load in full, `needsFullLoad()`: which fetched pages do, `syncDocument()`: what outside `<main>` is brought up to the next page), tested by `npm run test:frontend` in the starters' repository. The page's enhancements are set up per container by `enhance()`, whose listeners go when the page is swapped out (an `AbortController`); the header's are set once, and delegated, as only its children are replaced. Barba adds about 9 KB (gzipped) to the script; it's imported from its modern ES module build (`@barba/core/dist/barba.modern.mjs`), not the package's default UMD build.

## Changing the design

1. Copy `css/` and `js/` into the app's `resources/`, and merge `package.json`'s dev dependencies into the app's.
2. In `vite.config.js` (copy it, or add to the app's), give the Laravel plugin the starter's folder:

   ```js
   laravel({
       input: ['resources/css/app.css', 'resources/js/app.js'],
       buildDirectory: 'lumina',
       hotFile: 'public/lumina/hot',
       // fonts: … as in this vite.config.js
   }),
   ```

3. `npm install && npm run build`: Tailwind scans the app's `resources/views`, where the starter's templates now are, and writes `public/lumina`. `npm run dev` serves it hot (the `public/lumina/hot` file).

After a rebuild on a server with the whole-page cache, run `php artisan responsecache:clear`: kept pages name the previous build's files.
