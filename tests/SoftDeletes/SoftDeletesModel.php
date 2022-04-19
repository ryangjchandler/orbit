<?php

namespace Orbit\Tests\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Concerns\SoftDeletes;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class SoftDeletesModel extends Model implements IsOrbital
{
    use Orbital;
    use SoftDeletes;

    protected $guarded = [];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->source(__DIR__ . '/content');
    }
}
