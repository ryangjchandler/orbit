<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Orbit\Support;

class RefreshCommand extends Command
{
    protected $signature = 'orbit:refresh {--connection=}';

    protected $description = 'Remove the Orbit database and then rebuild it from the content source files.';

    public function handle(): int
    {
        $start = microtime(true);

        Artisan::call('orbit:clear');

        // Set the connection if we want to migrate and seed mysql
        if ($this->option('connection')) {
            Config::set('orbit.connection', $this->option('connection'));
        }

        Support::getOrbitalModels()
            ->tap(fn ($c) => $this->info('Found ' . $c->count() . ' Orbit models. Rebuilding database...'))
            // New up each model to trigger bootOrbital, which will migrate and force seed
            ->each(fn (string $modelFQN) => new $modelFQN);

        $this->info('✅ Rebuilt the Orbit database from content source files in ' . number_format(microtime(true) - $start, 2) . 's.');

        return 0;
    }
}
