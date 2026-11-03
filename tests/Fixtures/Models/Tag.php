<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

#[Table('post_tags')]
class Tag extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, PostTag::class);
    }
}
