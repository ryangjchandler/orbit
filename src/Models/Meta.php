<?php

namespace Orbit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @internal */
final class Meta extends Model
{
    protected $guarded = [];

    public static function booted()
    {
        $model = new static;

        if (! Schema::connection('orbit_meta')->hasTable($model->getTable())) {
            Schema::connection('orbit_meta')->create($model->getTable(), function (Blueprint $table) {
                $table->id();
                $table->string('orbital_type')->index();
                $table->string('orbital_key')->index();
                $table->string('file_path_read_from')->nullable();
            });
        }
    }

    public function orbital(): MorphTo
    {
        return $this->morphTo(type: 'orbital_type', id: 'orbital_key');
    }
}
