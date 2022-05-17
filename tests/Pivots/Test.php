<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orbit\Tests\Pivots\RoleUserPivot;
use Orbit\Tests\Pivots\User;

test('pivots > booting a model migrates its relations with pivots', function () {
    User::create(['name' => 'Ryan Chandler']);

    expect(Schema::connection('orbit')->hasTable('role_user_pivot'))->toBeTrue();

    $roles = User::first()->roles;

    expect($roles->count())->toBe(0);

    expect(Schema::connection('orbit')->hasTable('role_user_pivot'))->toBeTrue();
});

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__ . '/content');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/content');
});
