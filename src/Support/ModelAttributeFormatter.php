<?php

namespace Orbit\Support;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Orbit\Contracts\Orbit;

class ModelAttributeFormatter
{
    public static function format(Orbit & Model $model, array $attributes): array
    {
        $formatted = [];

        foreach ($attributes as $key => $value) {
            $value = $model->{$key};

            $formatted[$key] = match (true) {
                $value instanceof BackedEnum => $value->value,
                default => $value,
            };
        }

        return $formatted;
    }
}
