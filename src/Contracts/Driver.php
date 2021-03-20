<?php

namespace Orbit\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface Driver
{
    public function table(Blueprint $table);
}
