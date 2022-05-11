<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orbit\Facades\Orbit;
use Orbit\Models\Meta;

class ClearCommand extends Command
{
    protected $signature = 'orbit:clear';

    protected $description = 'Remove Orbit\s database and truncate Orbit\'s Meta table.';

    public function handle()
    {
        Meta::truncate();

        File::delete(Orbit::getCachePath());

        $this->info('Truncated orbit-meta.sqlite and deleted orbit.sqlite');

        return 0;
    }
}
