<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Sunrice\Cms\Actions\Entries\SaveDraft;
use Sunrice\Starters\Tests\RealEstate\Support\LuminaSite;

use function Pest\Laravel\get;

/*
 * Page transitions (Barba.js, resources/js/app.js): the layout's markup on every page, the links that load in full,
 * and the built script. Forms aren't Barba's (it only follows links): they always submit normally.
 */

/** The page's <body> tag, its one Barba container and what's before and after it. */
function barbaParts(string $html): array
{
    expect(preg_match('~<body\b([^>]*)>~', $html, $body))->toBe(1)
        ->and(substr_count($html, 'data-barba="container"'))->toBe(1);
    preg_match('~^(.*)<main\b([^>]*\bdata-barba="container"[^>]*)>(.*)</main>(.*)$~s', $html, $parts);
    expect($parts)->not->toBeEmpty();

    return ['body' => $body[1], 'before' => $parts[1], 'main' => $parts[2], 'content' => $parts[3], 'after' => $parts[4]];
}

it('marks up every page of the sitemap for page transitions, in both languages', function (): void {
    installStarter('real-estate');
    preg_match_all('#<loc>([^<]+)</loc>#', (string) get('/sitemap.xml')->getContent(), $locs);
    $paths = array_map(fn (string $url): string => (string) parse_url($url, PHP_URL_PATH), $locs[1]);
    expect($paths)->toContain('/id');

    foreach ($paths as $path) {
        $html = (string) get($path)->assertOk()->getContent();
        $parts = barbaParts($html);

        expect(str_contains($parts['body'], ' data-barba="wrapper"'))->toBeTrue("{$path}: no wrapper");
        // The body classes, for the script to swap in.
        preg_match('~\bclass="([^"]*)"~', $parts['body'], $bodyClass);
        expect(str_contains($parts['main'], 'id="main"'))->toBeTrue("{$path}: the container isn't #main")
            ->and(str_contains($parts['main'], 'data-body-class="'.$bodyClass[1].'"'))->toBeTrue("{$path}: data-body-class isn't the body's classes")
            ->and(preg_match('~data-barba-namespace="(entry|listing|term|search|taxonomy|page)"~', $parts['main']))->toBe(1, "{$path}: no namespace");
        // The container holds the page's content, the header and footer stay outside it.
        expect(str_contains($parts['content'], '<h1'))->toBeTrue("{$path}: the <h1> is outside the container")
            ->and(str_contains($parts['before'], 'data-site-header'))->toBeTrue("{$path}: the header is inside the container")
            ->and(str_contains($parts['after'], '<footer') && str_contains($parts['after'], 'data-page-announcer'))->toBeTrue("{$path}: the footer is inside the container")
            ->and(str_contains($parts['content'], 'data-site-header') || str_contains($parts['content'], '<footer'))->toBeFalse("{$path}: the container holds the header or footer");

        // Language switcher links load in full.
        preg_match_all('~<a\b[^>]*\bhreflang="[^"]*"[^>]*>~', $html, $switcher);
        expect($switcher[0])->toHaveCount(2, "{$path}: no switcher");
        foreach ($switcher[0] as $tag) {
            expect(str_contains($tag, ' data-barba-prevent'))->toBeTrue("{$path}: {$tag}");
        }
    }
});

it('names the container after the page shown', function (): void {
    LuminaSite::build();

    expect(barbaParts((string) get('/properties/villa-samudra')->getContent())['main'])->toContain('data-barba-namespace="entry"')
        ->and(barbaParts((string) get('/properties')->getContent())['main'])->toContain('data-barba-namespace="listing"');
});

it('loads previews in full', function (): void {
    $site = LuminaSite::build();
    $property = $site->entry('properties/villa-samudra');
    app(SaveDraft::class)->handle($property, ['title' => 'Villa Samudra (new photos)', 'version' => $property->version]);
    $url = URL::temporarySignedRoute('sunrice.cms.preview', now()->addMinutes(30), ['translation' => $property->id]);

    $html = (string) get($url)->assertOk()->getContent();
    expect(barbaParts($html)['body'])->not->toContain('data-barba');
    expect((string) get('/properties/villa-samudra')->getContent())->toContain('data-barba="wrapper"');
});

it('bundles Barba into the built script, with the lumina:page event', function (): void {
    $root = dirname(__DIR__, 2).'/packages/real-estate/starter/public/lumina';
    $manifest = json_decode((string) file_get_contents($root.'/manifest.json'), true);
    $file = is_array($manifest) ? ($manifest['resources/js/app.js']['file'] ?? null) : null;
    expect($file)->toBeString()->and(is_file($root.'/'.$file))->toBeTrue();

    expect((string) file_get_contents($root.'/'.$file))
        ->toContain('x-barba')             // Barba's request header
        ->toContain('[@barba/core]')
        ->toContain('lumina:page')
        ->toContain('data-sunrice-draft-banner');
});
