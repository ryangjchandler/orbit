<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
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
        $table->json('tags')->nullable();
        $table->dateTime('published_at')->nullable();
        $table->integer('views')->default(0);
    }

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function title(): Attribute
    {
        return Attribute::get(fn (string $value) => Str::headline($value));
    }
}
