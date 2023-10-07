<?php

namespace Orbit\Support;

use Illuminate\Database\Schema\Blueprint;

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
            } elseif ($column->get('default') !== null) {
                $attributes[$name] = $column->get('default');
            }
        }

        return $attributes;
    }
}
