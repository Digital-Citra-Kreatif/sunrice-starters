<?php

declare(strict_types=1);

use Sunrice\StarterKit\Installation;

use function Pest\Laravel\get;

/*
 * Every starter of this repository: it installs on a fresh app, and every
 * page its sitemap lists answers in the starter's own layout (its site header
 * carries `data-site-header`), not the CMS's plain fallback, once, with no
 * attribute holding a JSON object (a model echoed where a string belongs).
 */

// From the files: datasets are read before the app boots.
dataset('starters', fn () => array_map(fn (string $manifest): string => (string) json_decode((string) file_get_contents($manifest), true)['name'], glob(dirname(__DIR__).'/packages/*/starter/starter.json') ?: []));

it('installs, and every page in its sitemap answers in its layout', function (string $name) {
    $installation = installStarter($name);
    expect($installation->files(Installation::KEPT))->toBe([]);

    $sitemap = get('/sitemap.xml')->assertOk()->getContent();
    preg_match_all('#<loc>([^<]+)</loc>#', (string) $sitemap, $locs);
    expect($locs[1])->not->toBeEmpty();
    expect($locs[1])->toBe(array_values(array_unique($locs[1])), "{$name}: a page listed twice");
    foreach ($locs[1] as $url) {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $response = get($path);
        expect($response->status())->toBe(200, "{$name}: {$path}");
        $html = (string) $response->getContent();
        expect(str_contains($html, 'data-site-header'))->toBeTrue("{$name}: {$path} is not in the starter's layout")
            ->and(preg_match('/\s[\w:@.-]+="\{(&quot;|")/', $html))->toBe(0, "{$name}: {$path} has an attribute holding JSON");
    }
})->with('starters');
