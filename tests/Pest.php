<?php

use Orbit\Drivers\Markdown;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Start a new request. Orbit checks content files for changes once per request,
 * when it is first used, so anything changed on disk is picked up after this.
 */
function nextRequest(): void
{
    app()->forgetScopedInstances();
}

function content_path(string $path = ''): string
{
    return config('orbit.paths.content').($path === '' ? '' : DIRECTORY_SEPARATOR.$path);
}

/** Write a content file by hand, as a user editing their content would. */
function writeContent(string $path, string $contents): string
{
    $path = content_path($path);

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), recursive: true);
    }

    file_put_contents($path, $contents);

    return $path;
}

/** Parse a content file with the driver that wrote it. */
function readContent(string $path, string $driver = Markdown::class): array
{
    return (new $driver)->parse(file_get_contents(content_path($path)));
}
