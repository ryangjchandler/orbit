<?php

namespace Orbit;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Orbit\Actions\MaybeCreateOrbitDirectories;
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
                    ->startWith(function () {
                        $maybeCreateOrbitDirectories = new MaybeCreateOrbitDirectories();
                        $maybeCreateOrbitDirectories->execute();
                    })
                    ->askToStarRepoOnGitHub('ryangjchandler/orbit');
            })
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $config = $this->app->get(Repository::class);

        $config->set('database.connections.orbit', [
            'driver' => 'sqlite',
            'database' => $config->get('orbit.paths.database'),
            'foreign_key_constraints' => false,
        ]);
    }
}
