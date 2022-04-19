<?php

namespace Orbit;

use Orbit\Commands\UpgradeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
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

        $this->app['config']->set('database.connections.orbit_meta', [
            'driver' => 'sqlite',
            'database' => storage_path('framework/cache/orbit_meta.sqlite'),
            'foreign_key_constraints' => false,
        ]);
    }

    public function packageBooted()
    {
        if (! File::exists($metaPath = storage_path('framework/cache/orbit_meta.sqlite'))) {
            File::put($metaPath, '');
        }

        if (! Schema::connection('orbit_meta')->hasTable('metas')) {
            Schema::connection('orbit_meta')->create('metas', function (Blueprint $table) {
                $table->id();
                $table->string('orbital_type')->index();
                $table->string('orbital_key')->index();
                $table->string('file_path_read_from')->nullable();
            });
        }

        Blueprint::macro('hasColumn', function (string $name): bool {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return collect($this->getColumns())->firstWhere(fn (ColumnDefinition $columnDefinition) => $columnDefinition->get('name') === $name) !== null;
        });
    }
}
