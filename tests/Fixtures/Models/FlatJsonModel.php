<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\FlatJson;

class FlatJsonModel extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];
    public $timestamps = false;

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->longText('content')->nullable();
    }

    public function title(): Attribute
    {
        return Attribute::get(fn(string $value) => Str::headline($value));
    }

    public function getOrbitDriver(): string
    {
        return FlatJson::class;
    }
}
