<?php

namespace Orbit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Post extends Model
{
    use Orbital;

    protected $guarded = [];

    protected $hidden = [
        'slug',
    ];

    public static function schema(Blueprint $table)
    {
        $table->id();
        $table->string('title');
        $table->string('slug')->nullable();
        $table->text('content')->nullable();
    }

    public function getExampleAttribute()
    {
        return $this->slug;
    }

    public function setExampleAttribute($example)
    {
        $this->slug = $example;
    }
}
