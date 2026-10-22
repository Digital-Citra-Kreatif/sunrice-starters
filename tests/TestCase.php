<?php

declare(strict_types=1);

namespace Sunrice\Starters\Tests;

use Orchestra\Testbench\TestCase as Testbench;
use Spatie\Honeypot\HoneypotServiceProvider;
use Spatie\ResponseCache\ResponseCacheServiceProvider;
use Sunrice\Admin\AdminServiceProvider;
use Sunrice\Cms\CmsServiceProvider;
use Sunrice\StarterKit\StarterKitServiceProvider;
use Sunrice\Starters\RealEstate\RealEstateServiceProvider;
use Sunrice\Starters\Tests\Fixtures\PanelProvider;
use Sunrice\Starters\Tests\Fixtures\User;

use function Orchestra\Testbench\default_migration_path;
use function Orchestra\Testbench\load_migration_paths;

/** A Laravel app with the panel, the CMS, the starter kit and every starter of this repository. */
abstract class TestCase extends Testbench
{
    /** Each starter's service provider. */
    public const STARTERS = [RealEstateServiceProvider::class];

    protected function getPackageProviders($app): array
    {
        return [
            AdminServiceProvider::class, CmsServiceProvider::class, HoneypotServiceProvider::class, ResponseCacheServiceProvider::class,
            StarterKitServiceProvider::class, ...self::STARTERS, PanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('honeypot.enabled', false);
        load_migration_paths($app, default_migration_path());
    }
}
