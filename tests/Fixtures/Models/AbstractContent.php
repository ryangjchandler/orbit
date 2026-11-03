<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

// orbit:cache must skip models it can't instantiate.
abstract class AbstractContent extends Model implements Orbit
{
    use Orbital;
}
