<?php

namespace Orbit\Tests;

use Carbon\Carbon;
use Faker\Core\File;
use Illuminate\Support\Str;
use Orbit\Concerns\Orbital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;

class CustomFilePathModel extends Model
{
    use Orbital;

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public static function schema(Blueprint $table)
    {
        $table->string('slug')->unique();
        $table->string('published_at');
    }

    public static function getOrbitalPathPattern(): ?string
    {
        return '{published_at:Y-m-d}';
    }

    public function getKeyName()
    {
        return 'slug';
    }

    public function getIncrementing()
    {
        return false;
    }
}

class CustomFilePathsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem)->deleteDirectory(__DIR__ . '/content/custom_file_path_models');
    }

    /** @test */
    public function it_can_be_stored_in_a_custom_path()
    {
        CustomFilePathModel::create([
            'slug' => Str::slug('foo bar'),
            'published_at' => now(),
        ]);

        $this->assertFileExists(__DIR__ . '/content/custom_file_path_models/2022-02-12/foo-bar.md');
    }

    /** @test */
    public function it_can_read_files_stored_in_a_custom_path()
    {
        (new Filesystem)->ensureDirectoryExists(__DIR__ . '/content/custom_file_path_models/2022-04-01');

        (new Filesystem)->put(
            __DIR__ . '/content/custom_file_path_models/2022-04-01/rick-roll.md',
            <<<md
            ---
            slug: rick-roll
            published_at: 2022-04-01T15:50:10+00:00
            created_at: 2022-02-12T15:50:10+00:00
            updated_at: 2022-02-12T15:50:10+00:00
            ---
            md
        );

        $this->assertFileExists(__DIR__ . '/content/custom_file_path_models/2022-04-01/rick-roll.md');

        $record = CustomFilePathModel::find('rick-roll');

        $this->assertEquals('rick-roll', $record->slug);
        $this->assertInstanceOf(Carbon::class, $record->published_at);
        $this->assertEquals('2022-04-01', $record->published_at->format('Y-m-d'));
    }
}