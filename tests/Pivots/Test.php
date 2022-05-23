<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orbit\Tests\Pivots\Role;
use Orbit\Tests\Pivots\RoleUserPivot;
use Orbit\Tests\Pivots\User;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;

test('pivots > booting a model migrates its relations with pivots', function () {
    User::create(['name' => 'Ryan Chandler']);

    expect(Schema::connection('orbit')->hasTable('role_user_pivot'))->toBeTrue();

    $roles = User::first()->roles;

    expect($roles->count())->toBe(0);

    expect(Schema::connection('orbit')->hasTable('role_user_pivot'))->toBeTrue();
});

test('pivots > adding a pivot relationship model creates the file', function () {
    $u = User::create(['name' => 'Ryan Chandler']);
    $r = Role::create(['name' => 'head_honcho']);

    $u->roles()->save($r);

    expect($u->roles->count())->toBe(1);

    assertFileExists(__DIR__ . '/content/users/1.md');
    assertFileExists(__DIR__ . '/content/roles/1.md');
    assertFileExists(__DIR__ . '/content/role_user_pivots/1.md');
});

test('pivots > removing a pivot relationship model removes the file', function () {
    $u = User::create(['name' => 'Ryan Chandler']);
    $r = Role::create(['name' => 'head_honcho']);

    $u->roles()->save($r);

    $u = User::first();

    $u->roles()->detach($r->id);

    expect($u->roles->count())->toBe(0);

    assertFileExists(__DIR__ . '/content/users/1.md');
    assertFileExists(__DIR__ . '/content/roles/1.md');
    assertFileDoesNotExist(__DIR__ . '/content/role_user_pivots/1.md');
});

beforeEach(function () {
    Config::set('orbit.paths.content', __DIR__ . '/content');

    File::ensureDirectoryExists(__DIR__ . '/content');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/content');
});
