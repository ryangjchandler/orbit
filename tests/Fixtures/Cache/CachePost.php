<?php

namespace Orbit\Tests\Fixtures\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class CachePost extends Model
{
    use Orbital;

    public static function schema(Blueprint $table)
    {
        $table->id();
        $table->string('title');
        $table->string('slug')->nullable();
        $table->text('content')->nullable();
    }
}
