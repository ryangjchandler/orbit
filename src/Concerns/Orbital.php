<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Orbit\Actions\DeleteSourceFile;
use Orbit\Actions\InitialiseOrbitalTable;
use Orbit\Actions\MaybeCreateOrbitDirectories;
use Orbit\Actions\MaybeRefreshDatabaseContent;
use Orbit\Actions\SaveCompiledAttributesToFile;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\Markdown;
use Orbit\Exceptions\InvalidDriverException;
use Orbit\Support\ModelAttributeFormatter;
use Orbit\Support\ModelUsesSoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Orbit\Contracts\Orbit
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

        if (! $initialiseOrbitTable->shouldInitialise($model)) {
            $initialiseOrbitTable->migrate($model);
        }

        $maybeRefreshDatabaseContent = new MaybeRefreshDatabaseContent();

        if ($maybeRefreshDatabaseContent->shouldRefresh($model)) {
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
