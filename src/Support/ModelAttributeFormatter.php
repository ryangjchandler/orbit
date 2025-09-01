<?php

namespace RyanChandler\FlatFile\Support;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;

class ModelAttributeFormatter
{
    public static function format(InteractsWithFlatFiles&Model $model, array $attributes): array
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
