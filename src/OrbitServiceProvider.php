<?php

namespace Orbit;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Orbit\Contracts\Driver;
use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalForceDeleted;
use Orbit\Events\OrbitalUpdated;
use Orbit\Facades\Orbit;
use Orbit\Listeners\ProcessGitTransaction;

class OrbitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/orbit.php', 'orbit');

        $this->app->singleton('orbit', function ($app) {
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
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/orbit.php' => config_path('orbit.php'),
            ], 'orbit:config');
        }

        if ($this->app['config']->get('orbit.git.enabled')) {
            Event::listen(OrbitalCreated::class, [ProcessGitTransaction::class, 'created']);
            Event::listen(OrbitalUpdated::class, [ProcessGitTransaction::class, 'updated']);
            Event::listen(OrbitalDeleted::class, [ProcessGitTransaction::class, 'deleted']);
            Event::listen(OrbitalForceDeleted::class, [ProcessGitTransaction::class, 'deleted']);
        }

        Blueprint::macro('hasColumn', function (string $name): bool {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return collect($this->getColumns())->contains(
                fn (ColumnDefinition $column) => $column->get('name') === $name
            );
        });
    }
}
