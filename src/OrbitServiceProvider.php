<?php

namespace Orbit;

use Orbit\Commands\UpgradeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OrbitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('orbit')
            ->hasCommands([
                UpgradeCommand::class,
            ])
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton(Orbit::class, function () {
            return new Orbit();
        });

        /** @var Orbit $orbit */
        $orbit = $this->app[Orbit::class];

        $this->app['config']->set('database.connections.orbit', [
            'driver' => 'sqlite',
            'database' => $orbit->getCachePath(),
            'foreign_key_constraints' => false,
        ]);
    }
}
