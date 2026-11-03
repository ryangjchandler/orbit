<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

class PostTag extends Pivot implements Orbit
{
    use Orbital;

    public $incrementing = true;

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('post_id');
        $table->foreignId('tag_id');
    }
}
