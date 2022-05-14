<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;
use Orbit\Drivers\Json;
use Orbit\OrbitOptions;
use Illuminate\Support\Facades\File;


use function PHPUnit\Framework\assertFileExists;

class JsonModel extends Model implements IsOrbital
{
    use Orbital;

    protected $casts = [
        'content' => 'json',
    ];

    protected $guarded = [];

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->json('content');
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::default()
            ->driver(Json::class)
            ->source(__DIR__ . '/json');
    }
}

test('json > can write to a file', function () {
    JsonModel::create([
        'title' => 'foo',
        'content' => [
            'foo' => 'bar',
            'test' => [
                'baz' => 'bob',
            ],
        ],
    ]);

    assertFileExists(__DIR__ . '/json/1.json');
});

test('json > can read from a json file', function () {
    file_put_contents(__DIR__ . '/json/1.json', json_encode([
        'title' => 'Barbaz',
        'content' => [
            'foo' => [
                'bar' => 'baz',
            ],
            'car' => [
                'baz' => 'bob',
            ],
        ],
    ], JSON_PRETTY_PRINT));

    expect(JsonModel::first())
        ->toBeInstanceOf(JsonModel::class)
        ->title->toBe('Barbaz')
        ->content->toBeArray();
});

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__ . '/json');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/json');
});
