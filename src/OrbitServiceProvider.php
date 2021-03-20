<?php

namespace Orbit;

use Illuminate\Support\ServiceProvider;

class OrbitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/orbit.php', 'orbit');

        $this->app->singleton('orbit', function ($app) {
            $manager = new OrbitManager($app);

            foreach ($this->app['config']->get('orbit.drivers') as $key => $driver) {
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
    }
}
