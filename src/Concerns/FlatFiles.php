<?php

namespace RyanChandler\FlatFile\Concerns;

use Illuminate\Database\Eloquent\Model;
use RyanChandler\FlatFile\Actions\DeleteSourceFile;
use RyanChandler\FlatFile\Actions\InitializeFlatFileTable;
use RyanChandler\FlatFile\Actions\MaybeCreateFlatFileDirectories;
use RyanChandler\FlatFile\Actions\MaybeRefreshDatabaseContent;
use RyanChandler\FlatFile\Actions\SaveCompiledAttributesToFile;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;
use RyanChandler\FlatFile\Drivers\Markdown;
use RyanChandler\FlatFile\Exceptions\InvalidDriverException;
use RyanChandler\FlatFile\Support\ModelAttributeFormatter;
use RyanChandler\FlatFile\Support\ModelUsesSoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles
 * 
 * @phpstan-ignore trait.unused
 */
trait FlatFiles
{
    public static function bootFlatFiles()
    {
        $model = new static();

        $maybeCreateOrbitDirectories = new MaybeCreateFlatFileDirectories();
        $maybeCreateOrbitDirectories->execute($model);

        $driver = $model->getFlatFileDriver();

        if (! class_exists($driver)) {
            throw InvalidDriverException::make($driver);
        }

        $driver = app($driver);

        if (! $driver instanceof Driver) {
            throw InvalidDriverException::make($driver::class);
        }

        $initialiseOrbitTable = new InitializeFlatFileTable();
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

        static::created(function (InteractsWithFlatFiles&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::updated(function (InteractsWithFlatFiles&Model $model) use ($driver, $saveCompiledAttributesToFile) {
            $model->refresh();

            $attributes = ModelAttributeFormatter::format($model, $model->getAttributes());
            $compiledAttributes = $driver->compile($attributes);

            $saveCompiledAttributesToFile->execute($model, $compiledAttributes, $driver);
        });

        static::deleted(function (InteractsWithFlatFiles&Model $model) use ($driver) {
            if (ModelUsesSoftDeletes::check($model)) {
                return;
            }

            $deleteSourceFile = new DeleteSourceFile();
            $deleteSourceFile->execute($model, $driver);
        });
    }

    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection('flat-file');
    }

    public function getConnectionName()
    {
        return 'flat-file';
    }

    public function getFlatFileDriver(): string
    {
        return Markdown::class;
    }

    public function getFlatFileSource(): string
    {
        return str(static::class)
            ->classBasename()
            ->snake()
            ->lower()
            ->plural()
            ->toString();
    }
}
