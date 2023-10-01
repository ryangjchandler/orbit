<?php

namespace Orbit\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface Orbital
{
    public static function schema(Blueprint $table): void;
}
