<?php

namespace Orbit\Tests\CustomFilepath;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class StaticCustomFilepath extends Model implements IsOrbital
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->source(__DIR__ . '/static-content')
            ->generateFilenameUsing(function () {
                return 'static-folder/{title}';
            });
    }
}
