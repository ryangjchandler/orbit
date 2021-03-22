<?php

namespace Orbit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Concerns\SoftDeletes;

class SoftDeletedPost extends Model
{
    use Orbital;
    use SoftDeletes;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
    }
}
