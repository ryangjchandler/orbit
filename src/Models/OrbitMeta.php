<?php

namespace Orbit\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $orbital_type
 * @property string|int $orbital_key
 */
final class OrbitMeta extends Model
{
    protected $table = '_orbit_meta';

    protected $connection = 'orbit_meta';

    protected $guarded = [];

    public $timestamps = false;

    public function orbital()
    {
        $class = $this->orbital_type;

        return $class::find($this->orbital_key);
    }

    public static function forOrbital(Model $model)
    {
        return static::query()->where('orbital_type', $model::class)->where('orbital_key', $model->getKey())->first();
    }
}
