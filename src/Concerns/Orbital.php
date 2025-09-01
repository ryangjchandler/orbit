<?php

namespace RyanChandler\FlatFile\Concerns;

use Illuminate\Database\Eloquent\Model;
use RyanChandler\FlatFile\Actions\DeleteSourceFile;
use RyanChandler\FlatFile\Actions\InitialiseOrbitalTable;
use RyanChandler\FlatFile\Actions\MaybeCreateOrbitDirectories;
use RyanChandler\FlatFile\Actions\MaybeRefreshDatabaseContent;
use RyanChandler\FlatFile\Actions\SaveCompiledAttributesToFile;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\Orbit;
use RyanChandler\FlatFile\Drivers\Markdown;
use RyanChandler\FlatFile\Exceptions\InvalidDriverException;
use RyanChandler\FlatFile\Support\ModelAttributeFormatter;
use RyanChandler\FlatFile\Support\ModelUsesSoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \RyanChandler\FlatFile\Contracts\Orbit
 */
trait Orbital
{
    public static function bootOrbital()
    {
        $model = new static();

        $maybeCreateOrbitDirectories = new MaybeCreateOrbitDirectories();
        $maybeCreateOrbitDirectories->execute($model);

        $driver = $model->getOrbitDriver();

        if (! class_exists($driver)) {
            throw InvalidDriverException::make($driver);
        }

        $driver = app($driver);

        if (! $driver instanceof Driver) {
            throw InvalidDriverException::make($driver::class);
        }

        $initialiseOrbitTable = new InitialiseOrbitalTable();
        $maybeRefreshDatabaseContent = new MaybeRefreshDatabaseContent();
        $refreshed = false;

        if ($initialiseOrbitTable->shouldInitialise($model)) {
            $initialiseOrbitTable->migrate($model, $driver);
            $maybeRefreshDatabaseContent->refresh($model, $driver);

            $refreshed = true;
        }

        if (! $refreshed && $maybeRefreshDatabaseContent->shouldRefresh($model)) {
            $maybeRefreshDatabaseContent->refresh($model, $driver);
        }

        $saveCompiledAttributesToFile = new SaveCompiledAttributesToFile();

        static::created(function (Orbit&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::updated(function (Orbit&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::deleted(function (Orbit&Model $model) use ($driver) {
            if (ModelUsesSoftDeletes::check($model)) {
                return;
            }

            $deleteSourceFile = new DeleteSourceFile();
            $deleteSourceFile->execute($model, $driver);
        });
    }

    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection('orbit');
    }

    public function getConnectionName()
    {
        return 'orbit';
    }

    public function getOrbitDriver(): string
    {
        return Markdown::class;
    }

    public function getOrbitSource(): string
    {
        return str(static::class)
            ->classBasename()
            ->snake()
            ->lower()
            ->plural()
            ->toString();
    }
}
