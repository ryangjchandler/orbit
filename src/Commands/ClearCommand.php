<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Orbit\Facades\Orbit;

class ClearCommand extends Command
{
    protected $name = 'orbit:clear';

    protected $descripition = 'Clear the Orbit database cache.';

    public function handle()
    {
        if (Orbit::isTesting()) {
            return 0;
        }

        $path = Orbit::getDatabasePath();

        if (! file_exists($path)) {
            return 0;
        }

        unlink($path);

        $this->info('Succesfully cleared the Orbit cache.');

        return 0;
    }
}
