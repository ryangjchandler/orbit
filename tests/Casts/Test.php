<?php

use function PHPUnit\Framework\assertFileExists;
use Orbit\Tests\Casts\AddressValue;
use Orbit\Tests\Casts\UserModel;

test('casts > it casts a value object when using eloquent', function () {
    // TODO check this is the expected way to set the casted attribute 🤔
    // Shouldn't it be $u->address->line* = *; ?
    $u = new UserModel();
    $u->email = 'willywonka@example.net';
    $u->address->lineOne = 'Flat 1A';
    $u->address->lineTwo = '123 Fake St';
    $u->save();

    assertFileContains(__DIR__ . '/content/1.md', 'address_line_one: \'Flat 1A\'');

    expect(UserModel::first())
        ->address->toBeInstanceOf(AddressValue::class);

    $u = UserModel::first();
    $u->address->lineOne = 'Dojo number 36';
    $u->address->lineTwo = 'Wutang Avenue';
    $u->save();

    assertFileContains(__DIR__ . '/content/1.md', 'address_line_one: \'Dojo number 36\'');

    expect($u)
        ->address->toBeInstanceOf(AddressValue::class);
});

test('casts > it casts a value object when using manual file edit', function () {
    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    email: whatever@example.com
    address_line_one: 'Flat 1A'
    address_line_two: '123 Fake St'
    ---

    Hello world!
    md);

    assertFileExists(__DIR__ . '/content/1.md');

    expect(UserModel::first())
        ->address->toBeInstanceOf(AddressValue::class)
        ->address->lineOne->toBe('Flat 1A')
        ->address->lineTwo->toBe('123 Fake St');

});

afterEach(function () {
    UserModel::all()->each->delete();
});
