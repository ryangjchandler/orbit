<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Orbit\Concerns\Orbital;
use ReflectionClass;

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

        $this->findOrbitModels()
            ->tap(fn ($c) => $this->info('Found ' . $c->count() . ' Orbit models. Rebuilding database...'))
            // New up each model to trigger bootOrbital, which will migrate and force seed
            ->each(fn (string $modelFQN) => new $modelFQN);

        $this->info('✅ Rebuilt the Orbit database from content source files in ' . number_format(microtime(true) - $start, 2) . 's.');

        return 0;
    }

    protected function findOrbitModels(): Collection
    {
        $laravel = $this->getLaravel();

        return collect(File::allFiles($laravel->path()))
            ->map(function ($item) use ($laravel) {
                // Convert file path to namespace
                $path = $item->getRelativePathName();

                return '\\' . sprintf(
                    '%s%s',
                    $laravel->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );
            })
            ->filter(function ($class) {
                if (!class_exists($class)) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class) &&
                    !$reflection->isAbstract() &&
                    isset(class_uses_recursive($class)[Orbital::class]);
            });
    }
}
