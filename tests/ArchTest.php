<?php

declare(strict_types=1);

/*
 * Starters use only what Sunrice promises to keep: the panel's, the CMS's
 * and the starter kit's classes marked @api.
 */

arch('every starter file declares strict types')
    ->expect('Sunrice\Starters')
    ->toUseStrictTypes();

it('lets starters use only the public API of the panel, the CMS and the starter kit', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__).'/packages/*/src') ?: [] as $src) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all('/^use\s+(Sunrice\\\\(?:Admin|Cms|StarterKit)\\\\[A-Za-z0-9_\\\\]+)/m', (string) file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $class) {
                $doc = (new ReflectionClass($class))->getDocComment();
                if ($doc === false || ! str_contains($doc, '@api')) {
                    $offenders[] = $file->getBasename().' uses '.$class;
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps templates off the app\'s own classes', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__).'/packages/*/starter/views') ?: [] as $views) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views)) as $file) {
            if ($file->isFile() && preg_match('/\\\\?App\\\\/', (string) file_get_contents($file->getPathname())) === 1) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('ships the MIT license with every starter package', function () {
    $root = dirname(__DIR__);
    $license = (string) file_get_contents("{$root}/LICENSE");
    $missing = array_values(array_filter(
        glob("{$root}/packages/*", GLOB_ONLYDIR) ?: [],
        fn (string $package) => ! is_file("{$package}/LICENSE") || file_get_contents("{$package}/LICENSE") !== $license,
    ));

    expect($license)->toStartWith("MIT License\n\nCopyright (c) 2026 Digital Citra Kreatif")
        ->and(array_map('basename', $missing))->toBe([]);
});
