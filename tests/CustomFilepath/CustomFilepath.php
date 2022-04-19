<?php

namespace Orbit\Tests\CustomFilepath;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class CustomFilepath extends Model implements IsOrbital
{
    use Orbital;

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->timestamp('published_at');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->source(__DIR__ . '/content')
            ->generateFilenameUsing(function () {
                return '{published_at:Y-m-d}/{title}';
            });
    }
}
