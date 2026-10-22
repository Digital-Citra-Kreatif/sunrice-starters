<?php

declare(strict_types=1);

namespace Sunrice\Starters\Tests\Fixtures;

use Sunrice\Admin\Panel;
use Sunrice\Admin\PanelProvider as BasePanelProvider;
use Sunrice\Cms\CmsPlugin;

/** The test app's panel, with the CMS, as a site's own provider would have it. */
final class PanelProvider extends BasePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->plugin(CmsPlugin::make());
    }
}
