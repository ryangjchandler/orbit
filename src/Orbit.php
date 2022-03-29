<?php

namespace Orbit;

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
