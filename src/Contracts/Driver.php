<?php

namespace Orbit\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface Driver
{
    public function shouldRestoreCache(string $directory): bool;

    public function save(Model $model, string $directory): bool;

    public function delete(Model $model, string $directory): bool;

    public function all(string $directory): Collection;
}
