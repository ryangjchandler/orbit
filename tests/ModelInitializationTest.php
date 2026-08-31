<?php

namespace Orbit\Tests;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class CastPropertyModel extends Model
{
    use Orbital;

    protected $guarded = [];

    protected $casts = [
        'publish_date' => 'date',
    ];

    public static function schema(Blueprint $table)
    {
        $table->bigIncrements('id');
        $table->date('publish_date')->nullable();
    }
}

class CastMethodModel extends Model
{
    use Orbital;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
        ];
    }

    public static function schema(Blueprint $table)
    {
        $table->bigIncrements('id');
        $table->date('publish_date')->nullable();
    }
}

#[Table('custom_table_name')]
class TableAttributeModel extends Model
{
    use Orbital;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->bigIncrements('id');
        $table->string('name');
    }
}

class ModelInitializationTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['cast_property_models', 'cast_method_models', 'table_attribute_models'] as $directory) {
            foreach (glob(__DIR__.'/content/'.$directory.'/*.md') as $file) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_casts_declared_as_a_property_are_applied_when_building_the_cache()
    {
        file_put_contents(__DIR__.'/content/cast_property_models/1.md', "---\nid: 1\npublish_date: 2026-08-31\n---");

        $model = CastPropertyModel::first();

        $this->assertSame('2026-08-31', $model->publish_date->toDateString());
        $this->assertSame('2026-08-31 00:00:00', $model->getConnection()->table('cast_property_models')->value('publish_date'));
        $this->assertTrue(CastPropertyModel::whereDate('publish_date', '2026-08-31')->exists());
    }

    public function test_casts_declared_as_a_method_are_applied_when_building_the_cache()
    {
        file_put_contents(__DIR__.'/content/cast_method_models/1.md', "---\nid: 1\npublish_date: 2026-08-31\n---");

        $model = CastMethodModel::first();

        $this->assertSame('2026-08-31', $model->publish_date->toDateString());
        $this->assertSame('2026-08-31 00:00:00', $model->getConnection()->table('cast_method_models')->value('publish_date'));
        $this->assertTrue(CastMethodModel::whereDate('publish_date', '2026-08-31')->exists());
    }

    public function test_the_table_attribute_is_applied_when_building_the_cache()
    {
        if (! class_exists(Table::class)) {
            $this->markTestSkipped('The #[Table] attribute requires Laravel 13.');
        }

        file_put_contents(__DIR__.'/content/table_attribute_models/1.md', "---\nid: 1\nname: Ryan\n---");

        $this->assertSame('Ryan', TableAttributeModel::first()->name);
    }
}
