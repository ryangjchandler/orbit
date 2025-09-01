<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use RyanChandler\FlatFile\Concerns\Orbital;
use RyanChandler\FlatFile\Concerns\SoftDeletes;
use RyanChandler\FlatFile\Contracts\Orbit;

class Category extends Model implements Orbit
{
    use Orbital;
    use SoftDeletes;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->longText('content')->nullable();
    }
}
