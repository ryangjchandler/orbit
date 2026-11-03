<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Orbit\Index;

class ClearCommand extends Command
{
    protected $signature = 'orbit:clear';

    protected $description = 'Clear Orbit\'s cache so it is rebuilt from your content files.';

    public function handle(Index $index): int
    {
        $index->clear();

        $this->components->info('Orbit cache cleared.');

        return self::SUCCESS;
    }
}
