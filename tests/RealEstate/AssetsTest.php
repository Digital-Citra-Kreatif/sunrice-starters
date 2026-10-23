<?php

declare(strict_types=1);

/*
 * The built stylesheet in starter/public/lumina (resources/css/app.css, rebuilt as resources/README.md says).
 */
function luminaStylesheet(): string
{
    $root = dirname(__DIR__, 2).'/packages/real-estate/starter/public/lumina';
    $manifest = json_decode((string) file_get_contents($root.'/manifest.json'), true);
    $file = is_array($manifest) ? ($manifest['resources/css/app.css']['file'] ?? null) : null;
    expect($file)->toBeString()->and(is_file($root.'/'.$file))->toBeTrue();

    return (string) file_get_contents($root.'/'.$file);
}

it('styles the rich text editor\'s image presets', function (): void {
    expect(luminaStylesheet())
        ->toContain('.sunrice-img{')
        ->toContain('.sunrice-img-w-50{width:50%')
        ->toContain('.sunrice-img-left{float:left')
        ->toContain('.sunrice-img-right{float:right')
        ->toContain('.sunrice-img-center{margin-inline:auto')
        ->toContain('.prose:after{content:"";clear:both')
        ->toMatch('~@media \(width<=40rem\)\{\.sunrice-img:is\(\.sunrice-img-left,\.sunrice-img-right\)\{float:none~');
});
