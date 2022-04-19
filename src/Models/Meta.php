<?php

namespace Orbit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/** @internal */
final class Meta extends Model
{
    protected $guarded = [];

    public function orbital(): MorphTo
    {
        return $this->morphTo(type: 'orbital_type', id: 'orbital_key');
    }
}
