<?php

namespace Orbit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class JsonModel extends Model
{
    use Orbital;

    protected $guarded = [];

    protected static $driver = 'json';

    public static function schema(Blueprint $table)
    {
        $table->string('name');
    }

    public function getKeyName()
    {
        return 'name';
    }

    public function getIncrementing()
    {
        return false;
    }
}
