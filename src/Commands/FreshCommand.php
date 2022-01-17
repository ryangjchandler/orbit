<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Orbit\Facades\Orbit;

class FreshCommand extends Command
{
    protected $name = 'orbit:fresh';

    protected $description = 'Remove all existing Orbit data and start fresh.';

    public function handle()
    {
        $confirm = $this->confirm('Are you sure you want to remove all existing Orbit data?');

        if (! $confirm) {
            $this->warn('Cancelling...');

            return 0;
        }

        (new Filesystem())->deleteDirectory(
            Orbit::getContentPath()
        );

        $this->info('Successfully removed all existing Orbit data.');
    }
}
