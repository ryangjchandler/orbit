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
        return 'orbit';
    }

    public static function resolveConnection($connection = null)
    {
        return parent::resolveConnection('orbit');
    }
}
