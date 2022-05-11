<?php

namespace Orbit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Orbit\Commands\UpgradeCommand;
use Orbit\Commands\ClearCommand;
use Orbit\Commands\RefreshCommand;
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
                ClearCommand::class,
                RefreshCommand::class,
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

        $this->app['config']->set('database.connections.orbit_meta', [
            'driver' => 'sqlite',
            'database' => storage_path('framework/cache/orbit_meta.sqlite'),
            'foreign_key_constraints' => false,
        ]);
    }

    public function packageBooted()
    {
        Blueprint::macro('hasColumn', function (string $name): bool {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return collect($this->getColumns())->firstWhere(fn (ColumnDefinition $columnDefinition) => $columnDefinition->get('name') === $name) !== null;
        });
    }
}
