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

class DefaultDatabaseValuesTest extends TestCase
{
    protected function tearDown(): void
    {
        DefaultValues::all()->each->delete();
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
}
