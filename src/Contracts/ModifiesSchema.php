<?php

namespace Orbit\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface ModifiesSchema
{
    public function schema(Blueprint $table): void;
}
