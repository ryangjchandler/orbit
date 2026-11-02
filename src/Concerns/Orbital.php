<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\Markdown;
use Orbit\Index;

trait Orbital
{
    public static function bootOrbital(): void
    {
        // Saves and deletes run in a transaction (see below), so a failed file write
        // rolls back the cached row and the cache never holds what isn't on disk.
        static::saved(fn (Model&Orbit $model) => app(Index::class)->write($model));

        static::deleted(function (Model&Orbit $model) {
            // Soft deletes keep the file and record `deleted_at` in it.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                app(Index::class)->write($model);
            } else {
                app(Index::class)->delete($model);
            }
        });
    }

    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return $this->getConnection()->transaction(fn () => parent::delete());
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

        return $attributes;
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
