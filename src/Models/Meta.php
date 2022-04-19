<?php

namespace Orbit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * @internal
 * @property string|null $file_path_read_from
 */
final class Meta extends Model
{
    protected $guarded = [];

    protected $connection = 'orbit_meta';

    public $timestamps = false;

    public static function booted()
    {
        $model = new static();

        if (! File::exists(storage_path('framework/cache/orbit_meta.sqlite'))) {
            File::put(storage_path('framework/cache/orbit_meta.sqlite'), '');
        }

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
