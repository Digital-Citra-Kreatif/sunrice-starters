<?php

declare(strict_types=1);

namespace Sunrice\Starters\RealEstate;

use Illuminate\Support\Facades\Vite;

/**
 * The starter's built CSS, script and fonts live in public/lumina (with
 * their own Vite manifest), apart from the app's public/build: the
 * layout calls this before @fonts and @vite, so the app's own front end
 * (and its `npm run build`) never clashes with them. To change the CSS,
 * see this package's resources/README.md.
 *
 * @api
 */
final class Assets
{
    public const DIRECTORY = 'lumina';

    public static function use(): void
    {
        Vite::useBuildDirectory(self::DIRECTORY)->useHotFile(public_path(self::DIRECTORY.'/hot'));
    }
}
