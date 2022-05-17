<?php

namespace Orbit\Concerns\Internal;

/**
 * @internal
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HandlesOrbitConnection
{
    public function getConnectionName()
    {
        return config('orbit.connection');
    }

    public static function resolveConnection($connection = null)
    {
        return parent::resolveConnection(config('orbit.connection'));
    }
}
