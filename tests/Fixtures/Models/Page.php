<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

class Page extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;

    public function schema(Blueprint $table): void
    {
        $table->string('slug')->primary();
        $table->string('title');
    }
}
