<?php

namespace RyanChandler\FlatFile;

use Illuminate\Config\Repository;
use RyanChandler\FlatFile\Actions\MaybeCreateFlatFileDirectories;
use RyanChandler\FlatFile\Actions\MaybeCreateOrbitDirectories;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FlatFileServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('eloquent-flat-file')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function () {
                        $maybeCreateFlatFileDirectories = new MaybeCreateFlatFileDirectories();
                        $maybeCreateFlatFileDirectories->execute();
                    })
                    ->askToStarRepoOnGitHub('ryangjchandler/eloquent-flat-file');
            })
            ->hasConfigFile('flat-file')
            ->hasCommands([
                Commands\ClearCommand::class,
            ]);
    }

    public function packageRegistered()
    {
        $config = $this->app->get(Repository::class);

        $config->set('database.connections.flat-file', [
            'driver' => 'sqlite',
            'database' => $config->get('flat-file.paths.database'),
            'foreign_key_constraints' => false,
        ]);
    }
}
