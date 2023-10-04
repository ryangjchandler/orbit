<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearCommand extends Command
{
    protected $signature = 'orbit:clear {--force}';

    protected $description = 'Clear Orbit\'s cache.';

    public function handle()
    {
        if (! $this->option('force') && ! $this->confirm('Are you sure you want to clear Orbit\'s cache?')) {
            return self::SUCCESS;
        }

        $fs = new Filesystem();
        $fs->delete(config('orbit.paths.database'));

        $this->info('Cache cleared.');

        return self::SUCCESS;
    }
}
