<?php

declare(strict_types=1);

use Sunrice\StarterKit\Installation;

use function Pest\Laravel\get;

/*
 * Every starter of this repository: it installs on a fresh app, and every
 * page its sitemap lists answers.
 */

// From the files: datasets are read before the app boots.
dataset('starters', fn () => array_map(fn (string $manifest): string => (string) json_decode((string) file_get_contents($manifest), true)['name'], glob(dirname(__DIR__).'/packages/*/starter/starter.json') ?: []));

it('installs, and every page in its sitemap answers', function (string $name) {
    $installation = installStarter($name);
    expect($installation->files(Installation::KEPT))->toBe([]);

    $sitemap = get('/sitemap.xml')->assertOk()->getContent();
    preg_match_all('#<loc>([^<]+)</loc>#', (string) $sitemap, $locs);
    expect($locs[1])->not->toBeEmpty();
    foreach (array_unique($locs[1]) as $url) {
        $path = (string) parse_url($url, PHP_URL_PATH);
        expect(get($path)->status())->toBe(200, "{$name}: {$path}");
    }
})->with('starters');
