<?php

namespace RyanChandler\FlatFile\Contracts;

use Illuminate\Database\Schema\Blueprint;

interface ModifiesSchema
{
    public function schema(Blueprint $table): void;
}
