<?php

namespace Orbit\Tests\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\OrbitOptions;

class Role extends Model implements IsOrbital
{
    use Orbital;

    protected $guarded = [];

    protected $casts = [
        'published' => 'bool',
    ];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::make()
            ->source(__DIR__ . '/content/roles');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: User::class,
            table: 'role_user_pivot'
        )->using(RoleUserPivot::class);
    }
}
