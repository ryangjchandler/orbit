<?php

namespace Orbit\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use Orbit\OrbitOptions;

/**
 * @property-read \Orbit\Models\Meta $orbitMeta
 * @property string $orbit_file_path
 */
interface IsOrbital
{
    public static function schema(Blueprint $table): void;

    public static function getOrbitOptions(): OrbitOptions;
}
