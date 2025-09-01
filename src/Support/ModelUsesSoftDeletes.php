<?php

namespace RyanChandler\FlatFile\Support;

use Illuminate\Database\Eloquent\Model;
use RyanChandler\FlatFile\Concerns\SoftDeletes;
use RyanChandler\FlatFile\Contracts\Orbit;

class ModelUsesSoftDeletes
{
    public static function check(Orbit&Model $model): bool
    {
        $uses = class_uses_recursive($model);

        return in_array(SoftDeletes::class, $uses);
    }
}
