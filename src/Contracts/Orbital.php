<?php

namespace Orbit\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface Orbital
{
    /**
     * Define the structure of your Orbital model.
     */
    public static function schema(Blueprint $table): void;

    /**
     * Declare which driver the Orbital should use.
     *
     * @return class-string<\Orbit\Contracts\Driver>
     */
    public function getOrbitDriver(): string;
}
