# Sunrice starters

Starter sites for [Sunrice CMS](https://github.com/Digital-Citra-Kreatif/sunrice): each a whole site (structure, sample content, templates, assets and words) in a Composer package of its own, installed with [`sunrice/starter-kit`](https://github.com/Digital-Citra-Kreatif/sunrice/tree/main/packages/starter-kit).

| Starter | Package | |
| --- | --- | --- |
| `real-estate` | [`sunrice/starter-real-estate`](packages/real-estate) | Lumina Estates: a bilingual (English, Indonesian) real-estate agency: properties with filters, agents, neighbourhood guides, a journal, enquiry forms. |

```bash
composer require sunrice/starter-real-estate
php artisan sunrice:starter real-estate --dry-run
php artisan sunrice:starter real-estate
```

A starter copies its templates, assets and words into the site and imports its structure and content, so the site owns them from then on. Keep the package installed when its templates use its classes (each starter's README says).

## Developing

This repository is developed like the Sunrice monorepo: each folder of `packages/` is a package, published to its own read-only repository for Packagist. Sunrice itself (the panel, the CMS, the starter kit) comes from a checkout of [Digital-Citra-Kreatif/sunrice](https://github.com/Digital-Citra-Kreatif/sunrice) in `./sunrice` (git-ignored):

```bash
git clone https://github.com/Digital-Citra-Kreatif/sunrice.git sunrice
composer install
composer test      # every starter installs on a fresh app and every sitemap page answers; each starter's own tests
composer lint
composer analyse
```

To add a starter, read the starter kit's guide ([docs/starters.md](https://github.com/Digital-Citra-Kreatif/sunrice/blob/main/packages/starter-kit/docs/starters.md)) and `AGENTS.md`.
