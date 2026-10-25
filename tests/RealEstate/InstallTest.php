<?php

declare(strict_types=1);

use Sunrice\Cms\Models\EntryTranslation;
use Sunrice\Cms\Settings\SiteSettings;

/*
 * The whole starter, sample content and all: its Settings → Site and Settings → SEO → Structured data values.
 */
beforeEach(function (): void {
    installStarter('real-estate');
});

it('sets the homepage, the site\'s name and the publisher', function (): void {
    $settings = app(SiteSettings::class);
    $home = EntryTranslation::query()->where('locale', 'en')->where('slug', 'home')->sole();

    expect($settings->homepage)->toBe($home->entry_id)
        ->and($settings->name)->toBe('Lumina Estates')
        ->and($settings->publisherType)->toBe('RealEstateAgent')
        ->and($settings->telephone)->toStartWith('+62 ')
        ->and($settings->address)->not->toBe('');

    $this->get('/')->assertOk()
        ->assertSee('<title>Lumina Estates</title>', false)
        ->assertSee('"@type":"RealEstateAgent","@id":"'.url('/').'#publisher"', false);
    $this->get('/id')->assertOk()->assertSee('<html lang="id">', false);
});

it('gives the company global the publisher\'s made-up telephone and address', function (): void {
    $settings = app(SiteSettings::class);
    $company = cms_global('company', 'en');

    expect($company['phone'])->toBe($settings->telephone)
        ->and($company['address'])->toBe($settings->address)
        ->and($settings->telephone)->toContain(' 555 ')
        ->and($settings->address)->toContain('Contoh');
});

it('gives every Indonesian property an Indonesian address', function (): void {
    $this->get('/id/properti/vila-arunika-pererenan')->assertOk();
    expect(EntryTranslation::query()->where('locale', 'id')->where('slug', 'like', '%-id')->pluck('slug')->all())->toBe([]);
});
