<?php

namespace Orbit\Tests\Casts;

class AddressValue
{
    public string $lineOne;

    public string $lineTwo;

    public function __construct(string $address_line_one, string $address_line_two)
    {
        $this->lineOne = $address_line_one;
        $this->lineTwo = $address_line_two;
    }
}
