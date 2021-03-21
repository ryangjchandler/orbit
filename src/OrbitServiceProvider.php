<?php

namespace Orbit;

use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Orbit\Contracts\Driver;
use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalUpdated;
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
        }
    }
}
