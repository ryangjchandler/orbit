<?php

namespace Orbit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Orbit;

class ConfigureBlueprintFromModel
{
    public static function configure(Orbit&Model $model, Blueprint $blueprint): Blueprint
    {
        $model->schema($blueprint);

        if ($model->usesTimestamps()) {
            $blueprint->timestamps();
        }

        if (ModelUsesSoftDeletes::check($model)) {
            $blueprint->softDeletes();
        }

        return $blueprint;
    }
}
