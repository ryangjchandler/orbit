<?php

namespace Orbit\Tests\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class AddressCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): AddressValue
    {
        return new AddressValue(
            $attributes['address_line_one'],
            $attributes['address_line_two']
        );
    }

    public function set($model, $key, $value, $attributes)
    {
        if (!$value instanceof AddressValue) {
            throw new InvalidArgumentException('The given value is not an AddressValue instance.');
        }

        return [
            'address_line_one' => $value->lineOne,
            'address_line_two' => $value->lineTwo,
        ];
    }
}
