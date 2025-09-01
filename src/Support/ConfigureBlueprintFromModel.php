<?php

namespace RyanChandler\FlatFile\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;

class ConfigureBlueprintFromModel
{
    public static function configure(InteractsWithFlatFiles&Model $model, Blueprint $blueprint): Blueprint
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
