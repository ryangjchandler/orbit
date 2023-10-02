<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

class Post extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->longText('content')->nullable();
    }
}
