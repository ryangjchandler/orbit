<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\Json;

class SiteSetting extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function schema(Blueprint $table): void
    {
        $table->string('key')->primary();
        $table->json('value')->nullable();
    }

    public function getOrbitDriver(): string
    {
        return Json::class;
    }

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
