<?php

declare(strict_types=1);

use Sunrice\Cms\Models\Blueprint;
use Sunrice\Cms\Models\Collection;
use Sunrice\Cms\Models\Entry;
use Sunrice\Cms\Models\Taxonomy;
use Sunrice\Cms\Models\Term;

/*
 * Entries get their terms from their collection's taxonomies (the editor's Taxonomies card), not from terms
 * fields: the starter attaches them, one term only where a property has one, and gives its sample entries theirs.
 */
beforeEach(function (): void {
    installStarter('real-estate');
});

it('attaches the taxonomies to their collections, one term only for a property\'s type, listing type and place', function (): void {
    $single = fn (string $taxonomy, string $collection): mixed => Taxonomy::query()->where('handle', $taxonomy)->sole()
        ->collections()->where('handle', $collection)->sole()->getRelation('pivot')->single;

    expect($single('property_types', 'properties'))->toBeTrue()
        ->and($single('listing_types', 'properties'))->toBeTrue()
        ->and($single('locations', 'properties'))->toBeTrue()
        ->and($single('amenities', 'properties'))->toBeFalse()
        ->and($single('topics', 'journal'))->toBeFalse();
});

it('keeps no terms fields for the entries\' own terms in the blueprints', function (): void {
    foreach (['post', 'property'] as $handle) {
        $types = array_column(Blueprint::query()->where('handle', $handle)->sole()->fields, 'type');
        expect($types)->not->toContain('terms');
    }
});

it('gives the sample entries their terms', function (): void {
    $slugs = function (string $collection, string $slug): array {
        $entry = Entry::query()->whereHas('translations', fn ($q) => $q->where('slug', $slug))
            ->where('collection_id', Collection::query()->where('handle', $collection)->sole()->id)->sole();

        return $entry->terms->mapToGroups(fn (Term $t): array => [$t->taxonomy->handle => $t->translation('en')?->slug])->map->all()->all();
    };

    expect($slugs('properties', 'villa-arunika-pererenan'))->toEqual([
        'property_types' => ['villa'],
        'listing_types' => ['for-sale'],
        'locations' => ['canggu'],
        // In the taxonomy's order, as the editor lists them.
        'amenities' => ['private-pool', 'garden', 'parking', 'rice-field-view', 'smart-home', 'staff-quarters'],
    ])->and($slugs('journal', 'renting-a-family-home-in-south-jakarta-what-to-expect'))->toEqual(['topics' => ['buying-guides', 'living']]);
});

it('shows a sample property\'s and post\'s terms, linked to their pages', function (): void {
    $term = fn (string $taxonomy, string $slug): Term => Term::query()->whereHas('taxonomy', fn ($q) => $q->where('handle', $taxonomy))
        ->whereHas('translations', fn ($q) => $q->where('slug', $slug))->sole();

    $this->get('/properties/villa-arunika-pererenan')->assertOk()
        ->assertSee('For sale')
        ->assertSee('href="'.$term('property_types', 'villa')->url().'"', false)
        ->assertSee('href="'.$term('locations', 'canggu')->url().'"', false)
        ->assertSee('Rice-field view');
    $this->get('/journal/renting-a-family-home-in-south-jakarta-what-to-expect')->assertOk()
        ->assertSeeInOrder([$term('topics', 'buying-guides')->url(), $term('topics', 'living')->url()], false);
    $this->get('/properties?location=bali')->assertOk()->assertSee('Villa Arunika, Pererenan');
});

it('gives its terms only their shared pages, not the plain ones in each collection', function (): void {
    foreach (['amenities' => 'properties', 'listing_types' => 'properties', 'locations' => 'properties', 'property_types' => 'properties', 'topics' => 'journal'] as $taxonomy => $collection) {
        $pivot = Taxonomy::query()->where('handle', $taxonomy)->sole()->collections()->where('handle', $collection)->sole()->getRelation('pivot');
        expect($pivot->term_pages)->toBeFalse("{$taxonomy} in {$collection}")
            ->and($pivot->index_page)->toBeFalse("{$taxonomy} index in {$collection}");
    }

    $sitemap = (string) $this->get('/sitemap.xml')->assertOk()->getContent();
    // A collection's term and index pages are at {its listing page}/{taxonomy handle}, in every language.
    expect(preg_match('#<loc>[^<]*/(amenities|listing-types|locations|property-types|topics)(/[^<]*)?</loc>#', $sitemap))->toBe(0);
    $this->get('/properties/locations/canggu')->assertNotFound();
    $this->get('/properties/amenities/garden')->assertNotFound();
    $this->get('/location/canggu')->assertOk()->assertSee('data-site-header', false);
});

it('links a property\'s type and place to their term pages, in each language', function (): void {
    $this->get('/properties/villa-arunika-pererenan')->assertOk()
        ->assertSee('href="'.url('/property-type/villa').'"', false)
        ->assertSee('href="'.url('/location/canggu').'"', false);

    $this->get('/id/properti/vila-arunika-pererenan')->assertOk()
        ->assertSee('href="'.url('/id/tipe-properti/'), false)
        ->assertSee('href="'.url('/id/lokasi/'), false)
        ->assertDontSee('/id/properties/', false);
});

it('renders the inquiry form\'s fields with their own types, whatever the page calls its variables', function (): void {
    $html = (string) $this->get('/properties/villa-arunika-pererenan')->assertOk()->getContent();

    expect($html)->toContain('type="text" name="data[name]"')
        ->toContain('type="email" name="data[email]"')
        ->not->toContain('type="{');
});
