<?php

namespace Orbit\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Orbit\OrbitOptions;

trait Orbital
{
    abstract public static function schema(Blueprint $table): void;

    abstract public static function getOrbitOptions(): OrbitOptions;

    public static function bootOrbital()
    {
        $options = static::getOrbitOptions();
    }
}
