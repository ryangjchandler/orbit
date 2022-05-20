<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orbit\Facades\Orbit;

class ClearCommand extends Command
{
    protected $signature = 'orbit:clear';

    protected $description = 'Remove Orbit\s database.';

    public function handle()
    {
        Artisan::call('db:wipe --database=orbit');

        $this->info('Deleted Orbit\'s sqlite database');

        return 0;
    }
}
