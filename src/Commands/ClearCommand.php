<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Orbit\Actions\ClearCache;
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

        $confirm = $this->confirm('Are you sure you want to delete Orbit\'s cache file?');

        if (! $confirm) {
            $this->warn('Cancelling...');

            return 0;
        }

        (new ClearCache())();

        $this->info('Succesfully cleared the Orbit cache.');

        return 0;
    }
}
