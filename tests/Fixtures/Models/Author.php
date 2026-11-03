<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\Yaml;

class Author extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->text('api_token')->nullable();
        $table->text('recovery_codes')->nullable();
    }

    public function getOrbitDriver(): string
    {
        return Yaml::class;
    }

    public function getOrbitSource(): string
    {
        return 'people';
    }

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
