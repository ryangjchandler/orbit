<?php

namespace Orbit;

use Illuminate\Support\Facades\Config;

final class Orbit
{
    public function getCachePath(): string
    {
        return config('orbit.paths.cache');
    }

    public function getContentPath(): string
    {
        return config('orbit.paths.content');
    }
}
