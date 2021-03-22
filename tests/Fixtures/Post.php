<?php

namespace Orbit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Post extends Model
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
    }
}
