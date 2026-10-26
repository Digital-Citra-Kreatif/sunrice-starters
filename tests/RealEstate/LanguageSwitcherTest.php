<?php

declare(strict_types=1);

use Sunrice\Admin\Actions\Settings\SaveSettings;
use Sunrice\Cms\Settings\SiteSettings;
use Sunrice\Starters\Tests\RealEstate\Support\LuminaSite;

/*
 * The header's EN/ID pills come from the CMS's <x-sunrice-cms::language-switcher>: the page in each language, the
 * current one marked with aria-current, and, for a language the page isn't in, that language's homepage.
 */
beforeEach(function (): void {
    $this->site = LuminaSite::build();
});

/** The header's switcher, or null when the page has none. */
function languageSwitcher(string $html): ?string
{
    return preg_match('~<nav class="lang-switch"[^>]*>.*?</nav>~s', $html, $match) === 1 ? $match[0] : null;
}

it('links a translated page in each language, marking the current one', function (): void {
    $switcher = languageSwitcher($this->get('/properties/villa-samudra')->assertOk()->getContent());

    expect($switcher)->not->toBeNull()
        ->toContain('aria-label="Languages"')
        ->toContain('<a href="'.url('/properties/villa-samudra').'" hreflang="en" lang="en" data-barba-prevent aria-current="true">')
        ->toContain('<a href="'.url('/id/properti/vila-samudra').'" hreflang="id" lang="id" data-barba-prevent>')
        ->toContain('<span aria-hidden="true">ID</span>')
        ->toContain('<span class="visually-hidden">Bahasa Indonesia</span>');
    expect(languageSwitcher($this->get('/id/properti/vila-samudra')->getContent()))
        ->toContain('<a href="'.url('/id/properti/vila-samudra').'" hreflang="id" lang="id" data-barba-prevent aria-current="true">');
});

it('links an untranslated page at its Indonesian address with the main-language fallback on', function (): void {
    expect(languageSwitcher($this->get('/about')->assertOk()->getContent()))
        ->toContain('<a href="'.url('/id/about').'" hreflang="id" lang="id" data-barba-prevent data-fallback>');
});

it('keeps the switcher with the fallback off, linking the Indonesian homepage', function (): void {
    app(SaveSettings::class)->handle(app(SiteSettings::class), ['fallback' => false]);

    $this->get('/id/about')->assertNotFound();
    expect(languageSwitcher($this->get('/about')->assertOk()->getContent()))->not->toBeNull()
        ->toContain('<a href="'.url('/about').'" hreflang="en" lang="en" data-barba-prevent aria-current="true">')
        ->toContain('<a href="'.url('/id').'" hreflang="id" lang="id" data-barba-prevent data-home>');
});
