<?php

namespace Orbit;

use Exception;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Orbit\Actions\ClearCache;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;
use Orbit\Models\OrbitMeta;

class OrbitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/orbit.php', 'orbit');

        $this->app->scoped(OrbitManager::class, function ($app) {
            $manager = new OrbitManager($app);

            foreach ($this->app['config']->get('orbit.drivers') as $key => $driver) {
                $implements = class_implements($driver);

                if (! in_array(Driver::class, $implements)) {
                    throw new Exception('[Orbit] The '.$driver.' driver must implement the '.Driver::class.' interface.');
                }

                $manager->extend($key, fn () => new $driver($app));
            }

            return $manager;
        });

        $config = $this->app['config'];

        $config->set('database.connections.orbit', [
            'driver' => 'sqlite',
            'database' => Orbit::getDatabasePath(),
            'foreign_key_constraints' => false,
        ]);

        $config->set('database.connections.orbit_meta', [
            'driver' => 'sqlite',
            'database' => Orbit::getMetaDatabasePath(),
            'foreign_key_constraints' => false,
        ]);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CacheCommand::class,
                Commands\ClearCommand::class,
                Commands\FreshCommand::class,
                Commands\GenerateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/orbit.php' => config_path('orbit.php'),
            ], 'orbit:config');
        }

        if (! File::exists($metaPath = Orbit::getMetaDatabasePath())) {
            File::ensureDirectoryExists(dirname($metaPath), 0755, true);
            File::put($metaPath, '');
        }

        if (! Schema::connection('orbit_meta')->hasTable('_orbit_meta')) {
            Schema::connection('orbit_meta')->create('_orbit_meta', function (Blueprint $table) {
                $table->id();
                $table->string('orbital_type')->index();
                $table->string('orbital_key')->index();
                $table->string('file_path_read_from')->nullable();
            });
        } else {
            OrbitMeta::truncate();
        }

        Event::listen(DatabaseRefreshed::class, ClearCache::class);

        Blueprint::macro('hasColumn', function (string $name): bool {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return collect($this->getColumns())->contains(
                fn (ColumnDefinition $column) => $column->get('name') === $name
            );
        });

        Blueprint::macro('orbitGetColumn', function (string $name): ?ColumnDefinition {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return collect($this->getColumns())->firstWhere('name', $name);
        });
    }
}
