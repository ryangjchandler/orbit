<?php

namespace Orbit;

use Illuminate\Config\Repository;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OrbitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('orbit')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(fn () => is_dir(config('orbit.paths.content')) || mkdir(config('orbit.paths.content'), recursive: true))
                    ->askToStarRepoOnGitHub('ryangjchandler/orbit');
            })
            ->hasConfigFile()
            ->hasCommands([
                Commands\CacheCommand::class,
                Commands\ClearCommand::class,
            ]);
    }

    public function packageRegistered()
    {
        // Scoped so that long-running processes (Octane, queues) check files once per request or job.
        $this->app->scoped(Index::class);

        $config = $this->app->get(Repository::class);

        $config->set('database.connections.orbit', [
            'driver' => 'sqlite',
            'database' => $config->get('orbit.paths.database'),
            'foreign_key_constraints' => false,
            'busy_timeout' => 5000,
        ]);
    }
}
