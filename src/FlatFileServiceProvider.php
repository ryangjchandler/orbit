<?php

namespace RyanChandler\FlatFile;

use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;

class FlatFileServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flat-file.php', 'flat-file');

        $repository = $this->app->get(Repository::class);

        $this->app->singleton(FlatFile::class, static function () use ($repository): FlatFile {
            return new FlatFile(
                config: new Repository($repository->get('flat-file')),
            );
        });

        $repository->set('database.connections.flat-file', [
            'driver' => 'sqlite',
            'database' => $repository->get('flat-file.paths.database'),
            'foreign_key_constraints' => false,
            'journal_mode' => 'WAL',
            'busy_timeout' => 5000,
            'synchronous' => 'NORMAL',
            'transaction_mode' => 'DEFERRED',
        ]);
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/flat-file.php' => config_path('flat-file.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ClearCommand::class,
            ]);

            $this->optimizes(
                clear: 'orbit:clear --force',
            );
        }
    }
}
