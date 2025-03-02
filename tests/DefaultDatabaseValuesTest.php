<?php

namespace Orbit\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class DefaultValues extends Model
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->bigIncrements('id');
        $table->string('name');
        $table->string('email')->default('foo@test.com');
    }
}

class MissingRow extends Model
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->bigIncrements('id');
        $table->string('country')->default('United Kingdom');
    }
}

class DefaultDatabaseValuesTest extends TestCase
{
    protected function tearDown(): void
    {
        DefaultValues::all()->each->delete();

        parent::tearDown();
    }

    public function test_default_values_are_stored_on_disk()
    {
        $model = DefaultValues::create([
            'name' => 'Ryan',
        ]);

        $this->assertSame('foo@test.com', $model->email);

        $contents = file_get_contents(__DIR__.'/content/default_values/'.$model->id.'.md');

        $this->assertStringContainsString('email: foo@test.com', $contents);
    }

    public function test_missing_values_use_default_in_database()
    {
        file_put_contents(__DIR__ . '/content/missing_rows/1.md', <<<'md'
        ---
        id: 1
        ---
        md);

        file_put_contents(__DIR__ . '/content/missing_rows/2.md', <<<'md'
        ---
        id: 2
        country: Spain
        ---
        md);

        $this->assertCount(2, MissingRow::all());
        $this->assertEquals(1, MissingRow::first()->getKey());
        $this->assertEquals(2, MissingRow::find(2)->getKey());

        MissingRow::all()->each->delete();
    }
}
