<?php

namespace Orbit;

use Illuminate\Filesystem\Filesystem;
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
                        $fs = new Filesystem();

                        $fs->ensureDirectoryExists(base_path('content'));
                        $fs->ensureDirectoryExists(storage_path('framework/cache/orbit'));
                        $fs->ensureDirectoryExists(storage_path('framework/cache/orbit/database.sqlite'));
                    })
                    ->askToStarRepoOnGitHub('ryangjchandler/orbit');
            });
    }
}
