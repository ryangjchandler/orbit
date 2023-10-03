<?php

namespace Orbit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Orbit;

class FillMissingAttributeValuesFromBlueprint
{
    public static function fill(array $attributes, Blueprint $blueprint): array
    {
        foreach ($blueprint->getColumns() as $column) {
            $name = $column->name;

            if (array_key_exists($name, $attributes)) {
                continue;
            }

            if ($column->nullable) {
                $attributes[$name] = null;
            } elseif ($column->default) {
                $attributes[$name] = $column->default;
            }
        }

        return $attributes;
    }
}
