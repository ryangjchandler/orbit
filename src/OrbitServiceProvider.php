<?php

namespace Orbit;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OrbitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('orbit')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton(Orbit::class, function () {
            return new Orbit;
        });
    }
}
