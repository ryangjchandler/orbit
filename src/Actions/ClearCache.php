<?php

declare(strict_types=1);

namespace Orbit\Actions;

use Orbit\Facades\Orbit;

/** @internal */
final class ClearCache
{
    public function execute()
    {
        $path = Orbit::getDatabasePath();

        if (! file_exists($path)) {
            return 0;
        }

        unlink($path);
    }
}
