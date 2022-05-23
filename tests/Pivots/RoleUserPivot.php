<?php

namespace Orbit\Tests\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class RoleUserPivot extends Pivot implements IsOrbital
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->integer('user_id');
        $table->integer('role_id');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->default();
    }
}
