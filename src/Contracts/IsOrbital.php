<?php

namespace Orbit\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use Orbit\OrbitOptions;

/**
 * @property-read \Orbit\Models\Meta $orbitMeta
 */
interface IsOrbital
{
    public static function schema(Blueprint $table): void;

    public static function getOrbitOptions(): OrbitOptions;

    public function orbitMeta(): MorphOne;
}
