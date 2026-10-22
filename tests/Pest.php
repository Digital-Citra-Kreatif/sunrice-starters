<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Honeypot\Honeypot;
use Sunrice\StarterKit\Actions\InstallStarter;
use Sunrice\StarterKit\Installation;
use Sunrice\StarterKit\Starter;
use Sunrice\StarterKit\Starters;
use Sunrice\Starters\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(fn () => Storage::fake('public'))
    // A starter's files land in the test app: gone after each test.
    ->afterEach(fn () => removeInstalledFiles())
    ->in(__DIR__);

/** A form's honeypot fields, as a visitor who took their time sends them. */
function honeypot(): array
{
    config(['honeypot.enabled' => true, 'honeypot.amount_of_seconds' => 0]);
    $honeypot = app(Honeypot::class);

    return [$honeypot->nameFieldName() => '', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()];
}

/** Installs a starter of this repository on the test app, in its languages. */
function installStarter(string $name): Installation
{
    $starter = app(Starters::class)->find($name) ?? throw new RuntimeException("No starter \"{$name}\".");
    config(['sunrice-cms.locales' => $starter->locales !== [] ? $starter->locales : ['en']]);

    return app(InstallStarter::class)->handle($starter);
}

/** @internal */
function removeInstalledFiles(): void
{
    $files = new Filesystem;
    foreach (app(Starters::class)->all() as $starter) {
        foreach (Starter::FOLDERS as $folder) {
            $root = "{$starter->path}/{$folder}";
            if (! is_dir($root)) {
                continue;
            }
            $base = match ($folder) {
                'views' => resource_path('views'),
                'public' => public_path(),
                default => lang_path(),
            };
            foreach ($files->directories($root) as $directory) {
                $files->deleteDirectory($base.'/'.basename($directory));
            }
            foreach ($files->files($root) as $file) {
                $files->delete($base.'/'.$file->getFilename());
            }
        }
    }
}
