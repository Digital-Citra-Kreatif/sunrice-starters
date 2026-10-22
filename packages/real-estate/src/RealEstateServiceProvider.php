<?php

declare(strict_types=1);

namespace Sunrice\Starters\RealEstate;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sunrice\Admin\Support\AppUrl;
use Sunrice\Cms\Content as Cms;
use Sunrice\Cms\Settings\SiteSettings;
use Sunrice\Cms\Site\Page;
use Sunrice\StarterKit\Starters;

/**
 * The real-estate starter: registers its folder with the starter kit
 * (php artisan sunrice:starter real-estate) and gives its templates
 * what they need at run time: the helpers in Support (prices,
 * breadcrumbs, structured data), the "has-hero" body class, and the
 * listing filters' query parameters for the page cache. Keep the
 * package installed while the site uses these templates.
 */
final class RealEstateServiceProvider extends ServiceProvider
{
    /** The properties listing's filters and sort, and the agents' listings page name: kept pages are told apart by them. */
    public const QUERY = ['listing_type', 'property_type', 'location', 'bedrooms_min', 'bedrooms_max', 'price_min', 'price_max', 'sort', 'listings'];

    public function boot(): void
    {
        $this->callAfterResolving(Starters::class, fn (Starters $starters) => $starters->register(dirname(__DIR__).'/starter'));

        // robots.txt with the sitemap's full address (a static file can't know the host), on APP_URL rather than the
        // request's Host header; a site hidden from search engines (Settings → Site, or SUNRICE_NOINDEX) asks them to
        // stay out. An app's own robots route (named "robots") wins.
        $this->app->booted(function (): void {
            if (! $this->app->routesAreCached() && ! Route::has('robots')) {
                Route::middleware('web')->get('robots.txt', function (SiteSettings $site) {
                    $lines = $site->hidden()
                        ? ['User-agent: *', 'Disallow: /']
                        : ['User-agent: *', 'Disallow: /admin', '', 'Sitemap: '.AppUrl::route('sunrice.cms.sitemap')];

                    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
                })->name('robots');
            }
        });

        $query = config('sunrice-cms.cache.query', []);
        $query = is_array($query) ? array_filter($query, 'is_string') : [];
        config(['sunrice-cms.cache.query' => array_values(array_unique([...$query, ...self::QUERY]))]);

        // Pages that open with a full-bleed photo, and neighbourhood guides, get "has-hero": the header then sits
        // transparent over the photo. Reads the stored sections' block types only: no query, no hydration.
        Cms::bodyClassUsing(static function (Page $page): array {
            $entry = $page->entry;
            $data = $entry === null ? null : ($page->preview ? $entry->draft_data : $entry->data);
            if (! is_array($data)) {
                return [];
            }

            $sections = $data['sections'] ?? null;
            $first = is_array($sections) && is_array($sections[0] ?? null) ? $sections[0] : [];

            return match ($page->collection?->handle) {
                'pages' => ($first['type'] ?? null) === 'hero' ? ['has-hero'] : [],
                'neighbourhoods' => ['has-hero'],
                default => [],
            };
        });
    }
}
