<?php

namespace Orbit\Support;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Orbit\Contracts\Orbit;

class ModelAttributeFormatter
{
    public static function format(Orbit&Model $model, array $attributes): array
    {
        return Arr::map($attributes, static function (mixed $value, string $key) use ($model) {
            $cast = $model->{$key};

            return match (true) {
                $cast instanceof BackedEnum => $cast->value,
                $cast instanceof Carbon => $cast->toIso8601String(),
                default => $value,
            };
        });
    }
}
