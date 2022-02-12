<?php

namespace Orbit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** @internal */
final class Support
{
    public static function buildPathForPattern(string $pattern, Model $model)
    {
        $parts = explode('/', $pattern);
        $path = '';

        foreach ($parts as $i => $part) {
            if ($i !== 0) {
                $path .= '/';
            }

            // This part of the pattern is a binding. We need to strip the {} and get the property from the model.
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $binding = explode(':', trim($part, '{}'));

                [$property, $args] = count($binding) > 1 ?
                    [$binding[0], explode(',', $binding[1])] :
                    [$binding[0], []];

                $value = $model->{$property};

                if ($value instanceof Carbon && isset($args[0])) {
                    $path .= $value->format($args[0]);
                } else {
                    $path .= (string) $value;
                }
            } else {
                $path .= $part;
            }
        }

        return $path;
    }
}
