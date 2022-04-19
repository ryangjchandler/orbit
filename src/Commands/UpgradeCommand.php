<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Orbit\Facades\Orbit;

class UpgradeCommand extends Command
{
    protected $signature = 'orbit:upgrade';

    public function handle()
    {
        if (File::exists(Orbit::getCachePath())) {
            File::delete(Orbit::getCachePath());
        }
    }
}
