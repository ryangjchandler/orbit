<?php

namespace Orbit\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface Orbit
{
    /**
     * Define the structure of your Orbital model.
     */
    public function schema(Blueprint $table): void;

    /**
     * Declare which driver the Orbital should use.
     *
     * @return class-string<\Orbit\Contracts\Driver>
     */
    public function getOrbitDriver(): string;
}
