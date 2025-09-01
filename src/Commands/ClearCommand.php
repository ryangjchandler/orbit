<?php

namespace RyanChandler\FlatFile\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearCommand extends Command
{
    protected $signature = 'flat-file:cache:clear {--force}';

    protected $description = 'Clear the flat-file SQLite cache.';

    public function handle()
    {
        if (! $this->option('force') && ! $this->confirm('Are you sure you want to clear the flat-file SQLite cache?')) {
            return self::SUCCESS;
        }

        $fs = new Filesystem();
        $fs->delete(config('flat-file.paths.database'));

        $this->info('Cache cleared.');

        return self::SUCCESS;
    }
}
