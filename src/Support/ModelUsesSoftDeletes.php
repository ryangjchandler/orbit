<?php

namespace Orbit\Support;

use Illuminate\Database\Eloquent\Model;
use Orbit\Concerns\SoftDeletes;
use Orbit\Contracts\Orbit;

class ModelUsesSoftDeletes
{
    public static function check(Orbit&Model $model): bool
    {
        $uses = class_uses_recursive($model);

        return in_array(SoftDeletes::class, $uses);
    }
}
