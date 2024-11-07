<?php

use Illuminate\Database\Schema\Blueprint;
use Orbit\Support\BlueprintUtilities;

it('can determine column existence in blueprint', function () {
    $blueprint = new Blueprint('test');
    $blueprint->text('content')->nullable();

    expect(BlueprintUtilities::hasColumn($blueprint, 'content'))->toBeTrue();
});
