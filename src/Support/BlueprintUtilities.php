<?php

namespace Orbit\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/** @internal */
class BlueprintUtilities
{
    public static function hasColumn(Blueprint $table, string $column): bool
    {
        /* @phpstan-ignore-next-line */
        return collect($table->getColumns())->contains(static fn (ColumnDefinition $definition): bool => $definition->name === $column);
    }
}
