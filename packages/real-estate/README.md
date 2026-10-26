# sunrice/starter-real-estate

**Lumina Estates**: a premium residential agency's website for [Sunrice CMS](https://github.com/Digital-Citra-Kreatif/sunrice), in English and Indonesian. Properties with filters, agents, neighbourhood guides, a journal with comments, testimonials, enquiry, valuation and newsletter forms, structured data for properties and agents, and Indonesian addresses of its own (`/id/properti/…`, `/id/tim`, `/id/kawasan`, `/id/jurnal`).

Developed in [Digital-Citra-Kreatif/sunrice-starters](https://github.com/Digital-Citra-Kreatif/sunrice-starters); this package's own repository is a read-only copy.

## Install

On a Laravel app with Sunrice installed (`sunrice:install`), its languages set to English and Indonesian:

```bash
composer require sunrice/starter-real-estate        # brings sunrice/starter-kit
# .env: SUNRICE_LOCALES=en,id
php artisan sunrice:starter real-estate --dry-run   # what it would do
php artisan sunrice:starter real-estate             # structure, sample content, templates, assets, words
```

It sets **Settings → Site** (the homepage, the page *Home*, and the site's name, Lumina Estates) and **Settings → SEO → Structured data** (the publisher, a `RealEstateAgent`, with an example telephone and address) where the site still has the defaults: put in the agency's own there. Photos aren't included: add them to the properties, agents and guides in the media library.

`--no-content` installs the structure, templates and words without the sample properties, agents and posts.

**Keep the package installed**: the templates use its classes at run time (`Support\Money` for prices, `Support\Breadcrumbs`, `Support\Schema` for structured data, `Assets` for the built CSS), and its service provider adds the `has-hero` body class, the listing filters' parameters to the page cache (`sunrice-cms.cache.query`) and a `/robots.txt` naming the sitemap (unless the app has its own route named `robots`).

## What lives where

| In the CMS (editors, `/admin`) | In the site's files (developers) |
| --- | --- |
| Every word visitors read on pages: pages and their sections, properties, agents, guides, journal posts, testimonials | The templates (`resources/views/cms`, copied by the installer), how each kind of content is laid out |
| Prices, statuses, photos (with alt texts), floor plans | The built CSS, script, fonts and icons (`public/lumina`); their sources in this package's `resources/` |
| Menus (`main`, `footer`, `legal`), contact details (global `company`), social links (`social`), footer texts (`footer`) | Interface words: labels, buttons, headings of fixed sections, in `lang/en/site.php` and `lang/id/site.php` |
| Forms (`contact`, `inquiry`, `valuation`, `newsletter`): fields, recipients, thank-you texts | Which form appears where (contact page, property pages, sell page, footer) |
| SEO titles, descriptions, share images; redirects; site code; the publisher in structured data (**Settings → SEO → Structured data**: type, name, logo, telephone, address) | Structured data for properties, agents and FAQs (this package's `Support\Schema`) |

## The content model

| Collection | URL (Indonesian) | Template | Listing |
| --- | --- | --- | --- |
| `pages` (hierarchical) | `/{slug}`, the homepage at `/` | `cms/collections/pages/show`; Contact `cms.contact`, Sell with us `cms.sell` (their Page settings) | — |
| `properties` | `/properties/{slug}` (`/id/properti/…`) | `cms/collections/properties/show`, `…/index` | `/properties` with filters |
| `agents` (ordered) | `/team/{slug}` (`/id/tim/…`) | `cms/collections/agents/show`, `…/index` | `/team` |
| `neighbourhoods` | `/neighbourhoods/{slug}` (`/id/kawasan/…`) | `cms/collections/neighbourhoods/show`, `…/index` | `/neighbourhoods` |
| `journal` (dated, comments) | `/journal/{slug}` (`/id/jurnal/…`) | `cms/collections/journal/show`, `…/index` | `/journal`, 6 a page |
| `testimonials` (ordered) | none: shown by the Testimonials block | — | — |

| Taxonomy | Attached to | Terms per entry |
| --- | --- | --- |
| `property_types` | `properties` | one |
| `listing_types` (for sale, for rent) | `properties` | one |
| `locations` (hierarchical: Bali › Badung › Canggu) | `properties` | one |
| `amenities` | `properties` | several |
| `topics` | `journal` | several |

An entry's terms are its own, chosen in the editor's **Taxonomies** card (one term only where the attachment says so), not blueprint fields; templates read them with `$entry->termsOf('locations')` (see the CMS's [taxonomies](https://github.com/Digital-Citra-Kreatif/sunrice/blob/main/packages/cms/docs/taxonomies.md#giving-entries-terms)), and the property filters, term pages and "nearby" lists find entries by them.

Term pages: `/property-type/{slug}`, `/location/{slug}` (with the places under it), `/journal/topic/{slug}`, with `cms/taxonomies/{taxonomy}/show` (`property_types`, `locations`, `topics`); any other entry `cms/collections/show`. Templates go by folder: see the CMS's [templates](https://github.com/Digital-Citra-Kreatif/sunrice/blob/main/packages/cms/docs/site.md#templates). Search `/search`; the sitemap `/sitemap.xml`.

### Adding a block type

1. In the admin, create a fieldset (say `video`, with a `url` link and a `caption`) and add it to the `page` blueprint's `sections` field.
2. Create `resources/views/cms/blocks/video.blade.php`. It gets `$block` (`$block->url`, `$block->caption`), `$first` (whether it opens the page: then it holds the `<h1>`) and `$blockId` (an anchor).
3. Interface words go in `lang/en/site.php` and `lang/id/site.php` (`blocks.*`).

## Changing the design

The installed `public/lumina` holds the built CSS, script and fonts; this package's [`resources/`](resources/README.md) has their sources (Tailwind CSS v4, self-hosted fonts). To change them, copy the sources into the app and build into `public/lumina`, as `resources/README.md` describes.

## Page transitions

Links between the site's pages fade the content out and the next page in, without reloading the header, footer, fonts and script ([Barba.js](https://barba.js.org), bundled into the built script; `resources/js/app.js` and `transitions.js`). Without JavaScript, or when anything goes wrong (a timeout, an error page, a hook failing), the page simply loads in full: every page works without the script.

- **How**: `<body data-barba="wrapper">` in `cms/layout.blade.php` turns it on, and `<main id="main" data-barba="container" data-barba-namespace="…">` (the page's type: `entry`, `listing`, `term`, `search`, `taxonomy`, or `page` for the app's own pages) is what's swapped. The header and footer stay; their `data-swap` parts (the menus with their active states, the language switcher, the footer) are replaced with the next page's. The script also updates `document.title`, `<html lang>`, the SEO tags in the `<head>` (description, robots, canonical, hreflang, Open Graph and Twitter tags, structured data) and the body classes (the container's `data-body-class`, `Cms::bodyClass()`), scrolls to the top, focuses `#main`, says the new title in a polite live region for screen readers, and runs the gallery viewer, reveal on scroll and filter selects again on the new content.
- **Turning them off**: remove `data-barba="wrapper"` from `<body>`. The rest of the markup is harmless without it.
- **Reduced motion**: with `prefers-reduced-motion: reduce` the pages are swapped with no animation.
- **What loads in full**: forms (Barba only follows links, so the inquiry, contact, newsletter and search forms and the property filters submit as usual), the language switcher (`data-barba-prevent` on its links, and any link with `hreflang`), links to other sites, `mailto:`/`tel:`, downloads, `target="_blank"`, `#` anchors and links with one, files (`.pdf`, `.xml`, …), `/admin` and `/storage` (`FULL_LOAD_PATHS` in `transitions.js`: add the panel's path if it isn't `/admin`), and anything with `data-barba-prevent` (`data-barba-prevent="all"` on an element covers the links inside it). A next page whose content has `<script>` or `<style>` of its own (a captcha, a map embed) and pages with the CMS's draft banner (signed-in editors) load in full too, and previews and drafts don't swap pages at all.
- **Site code and analytics**: code outside `<main>`, including **Settings → Site code** and an analytics snippet, runs on the first page only. After each swapped page the script fires a `lumina:page` event on `document` with `detail: { url, namespace, container, trigger }` (`trigger`: `link`, `back`, `forward` or `popstate`). Count page views there:

  ```html
  <script>
      document.addEventListener('lumina:page', (event) => {
          gtag('event', 'page_view', { page_location: event.detail.url, page_title: document.title });
      });
  </script>
  ```

  It isn't fired for the first page, which the snippet counts itself.
- **Content Security Policy**: nothing inline is added, so it works with `SUNRICE_SITE_CSP_ENABLED=true`: the next page is fetched from the site itself (`connect-src 'self'`, in the CMS's policy) and only its container and the tags above are taken from it.
- **Page cache**: the script fetches the same HTML pages visitors get, so kept pages serve it too; it sends cookies as any same-site request does, and forms keep their CSRF token.

## Languages

English is the main language (no prefix), Indonesian under `/id`. An untranslated page shows the English content at its `/id/…` address, marked `lang="en"` (the CMS's fallback). The site's own words are in `lang/{en,id}/site.php`: keep both in step (the starters' tests check they have the same keys).

## Caching and deploying

- Whole pages: `SUNRICE_PAGE_CACHE=true` (`spatie/laravel-responsecache`). The property filters' parameters are added to `sunrice-cms.cache.query` by this package.
- On deploy, after rebuilding the CSS: `php artisan responsecache:clear` (kept pages name the old build's files).
- Keep a queue worker (notifications, image sizes) and the scheduler (scheduled publishing, pruning) running; see the panel's installation guide.

## History

Lumina was built as Sunrice's field test: an audit of 103 findings across admin, developer, editor, frontend and security testing, with re-tests after each round of fixes. The reports and screenshots are in the main repository's history (`examples/real-estate/qa/` until it moved here).
