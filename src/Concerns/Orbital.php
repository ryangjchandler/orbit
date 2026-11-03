<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Orbit\Drivers\Markdown;
use Orbit\Index;
use Orbit\Relations\BelongsToMany;

trait Orbital
{
    // Writes run in a transaction and write the file afterwards, so a failed file
    // write rolls back the cached row. Files are written here rather than in model
    // events so that quiet saves and increments still reach the file.
    public function save(array $options = [])
    {
        $previousKey = $this->exists ? $this->getOriginal($this->getKeyName()) : null;

        return $this->getConnection()->transaction(function () use ($options, $previousKey) {
            if (! parent::save($options)) {
                return false;
            }

            app(Index::class)->write($this, $previousKey);

            return true;
        });
    }

    public function delete()
    {
        return $this->getConnection()->transaction(function () {
            $deleted = parent::delete();

            if (! $deleted) {
                return $deleted;
            }

            // Soft deletes keep the file and record `deleted_at` in it.
            if (method_exists($this, 'isForceDeleting') && ! $this->isForceDeleting()) {
                app(Index::class)->write($this);
            } else {
                app(Index::class)->delete($this);
            }

            return $deleted;
        });
    }

    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        return $this->writeAfter(fn () => parent::incrementOrDecrement($column, $amount, $extra, $method));
    }

    protected function incrementOrDecrementEach(array $columns, array $extra, string $method)
    {
        return $this->writeAfter(fn () => parent::incrementOrDecrementEach($columns, $extra, $method));
    }

    private function writeAfter(callable $update): int|false
    {
        return $this->getConnection()->transaction(function () use ($update) {
            // A model that doesn't exist yet runs a bulk query instead, which doesn't write files.
            $exists = $this->exists;
            $result = $update();

            if ($exists && $result !== false) {
                app(Index::class)->write($this);
            }

            return $result;
        });
    }

    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    public function getConnection()
    {
        app(Index::class)->ensure($this);

        return static::resolveConnection();
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

    public function toOrbitAttributes(): array
    {
        $attributes = [$this->getKeyName() => $this->getKey()] + $this->getAttributes();

        foreach ($attributes as $key => $value) {
            if ($value === null || $this->isEncryptedCastable($key)) {
                continue;
            }

            if ($this->isJsonCastable($key) && is_string($value)) {
                $attributes[$key] = json_decode($value, true);
            } elseif ($this->isDateAttribute($key)) {
                $attributes[$key] = $this->asDateTime($value)->toIso8601String();
            }
        }

        // Leave out nulls so files only contain values that are set.
        return array_filter($attributes, fn ($value) => $value !== null);
    }

    public function fromOrbitAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if ($value === null || $this->isEncryptedCastable($key)) {
                continue;
            }

            if ($this->isJsonCastable($key) && ! is_string($value)) {
                $attributes[$key] = json_encode($value);
            } elseif ($this->isDateAttribute($key)) {
                $attributes[$key] = $this->fromDateTime($value);
            }
        }

        return $attributes;
    }
}
