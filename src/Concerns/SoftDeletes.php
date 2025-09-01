<?php

namespace RyanChandler\FlatFile\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes as BaseSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use RyanChandler\FlatFile\Actions\DeleteSourceFile;
use RyanChandler\FlatFile\Actions\SaveCompiledAttributesToFile;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\Orbit;
use RyanChandler\FlatFile\Exceptions\InvalidDriverException;
use RyanChandler\FlatFile\Support\ModelAttributeFormatter;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \RyanChandler\FlatFile\Contracts\Orbit
 */
trait SoftDeletes
{
    use BaseSoftDeletes;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);

        $model = new static();
        $driver = $model->getOrbitDriver();

        if (! class_exists($driver)) {
            throw InvalidDriverException::make($driver);
        }

        $driver = app($driver);

        if (! $driver instanceof Driver) {
            throw InvalidDriverException::make($driver::class);
        }

        $saveCompiledAttributesToFile = new SaveCompiledAttributesToFile();

        static::deleted(function (Orbit&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::restored(function (Orbit&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::forceDeleted(function (Orbit&Model $model) use ($driver) {
            $deleteSourceFile = new DeleteSourceFile();
            $deleteSourceFile->execute($model, $driver);
        });
    }
}
