<?php

declare(strict_types=1);

namespace Orbit\Actions;

use Orbit\Facades\Orbit;

/** @internal */
class ClearCache
{
    public function __invoke(): void
    {
        $path = Orbit::getDatabasePath();

        if (! file_exists($path)) {
            return;
        }

        unlink($path);
    }
}
