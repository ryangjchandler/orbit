<?php

namespace Orbit\Tests\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class UserModel extends Model implements IsOrbital
{
    use Orbital;

    protected $guarded = [];

    protected $casts = [
        'address' => AddressCast::class,
    ];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('email');
        $table->string('address_line_one');
        $table->string('address_line_two');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->source(__DIR__ . '/content');
    }
}
