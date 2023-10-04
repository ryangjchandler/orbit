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
            $name = $column->get('name');

            if (array_key_exists($name, $attributes)) {
                continue;
            }

            if ($column->get('nullable')) {
                $attributes[$name] = null;
            } elseif ($default = $column->get('default')) {
                $attributes[$name] = $default;
            }
        }

        return $attributes;
    }
}
