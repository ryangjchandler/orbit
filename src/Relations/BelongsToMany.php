<?php

namespace Orbit\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;
use Orbit\Contracts\Orbit;
use Orbit\Index;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 * @template TPivotModel of \Illuminate\Database\Eloquent\Relations\Pivot = \Illuminate\Database\Eloquent\Relations\Pivot
 * @template TAccessor of string = 'pivot'
 *
 * @extends BaseBelongsToMany<TRelatedModel, TDeclaringModel, TPivotModel, TAccessor>
 */
class BelongsToMany extends BaseBelongsToMany
{
    /**
     * The relationship joins the pivot table without going through the pivot model,
     * so its table has to be built here rather than when the pivot model is first used.
     *
     * @template TNewPivotModel of \Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @param  class-string<TNewPivotModel>  $class
     * @return $this
     *
     * @phpstan-this-out static<TRelatedModel, TDeclaringModel, TNewPivotModel, TAccessor>
     */
    public function using($class)
    {
        if (is_subclass_of($class, Orbit::class)) {
            app(Index::class)->ensure(new $class);
        }

        return parent::using($class);
    }
}
