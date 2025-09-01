<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use RyanChandler\FlatFile\Concerns\FlatFiles;
use RyanChandler\FlatFile\Concerns\SoftDeletes;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;

class Category extends Model implements InteractsWithFlatFiles
{
    use FlatFiles;
    use SoftDeletes;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->longText('content')->nullable();
    }
}
