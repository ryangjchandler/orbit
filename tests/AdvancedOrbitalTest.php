<?php

namespace Orbit\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orbit\Concerns\Orbital;
use Orbit\Tests\Fixtures\CustomKey;
use Orbit\Tests\Fixtures\JsonModel;
use Orbit\Tests\Fixtures\Post;
use Orbit\Tests\Fixtures\YamlModel;

class AdvancedOrbitalTest extends TestCase
{
    public function tearDown(): void
    {
        CustomKey::all()->each->delete();
        JsonModel::all()->each->delete();
        YamlModel::all()->each->delete();
        Post::all()->each->delete();

        parent::tearDown();
    }

    public function test_it_can_create_files_using_custom_primary_key()
    {
        $custom = CustomKey::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/custom_keys/'.$custom->name.'.md');
    }

    public function test_it_can_update_files_using_custom_primary_key()
    {
        $custom = CustomKey::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/custom_keys/'.$custom->name.'.md');

        $custom->update([
            'name' => 'John',
        ]);

        $this->assertFileDoesNotExist(__DIR__.'/content/custom_keys/Ryan.md');
        $this->assertFileExists(__DIR__.'/content/custom_keys/'.$custom->name.'.md');
    }

    public function test_it_can_use_json_driver()
    {
        $json = JsonModel::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/json_models/'.$json->getKey().'.json');
    }

    public function test_it_can_use_yaml_driver()
    {
        $yaml = YamlModel::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/yaml_models/'.$yaml->getKey().'.yml');
    }

    public function test_it_writes_hidden_columns()
    {
        $post = Post::create([
            'title' => 'Ryan',
            'slug' => 'ryan',
        ]);

        $this->assertFileExists($path = __DIR__.'/content/posts/'.$post->getKey().'.md');

        $contents = file_get_contents($path);

        $this->assertStringContainsString('slug', $contents);
    }

    public function test_mutators_and_accessors()
    {
        $post = Post::create([
            'title' => 'Accessors',
            'slug' => 'accessors',
        ]);

        $this->assertEquals($post->slug, $post->example);

        $post->example = 'cool';

        $this->assertEquals('cool', $post->example);
    }

    public function test_orbit_can_be_conditionally_disabled()
    {
        Schema::dropIfExists('conditionally_disableds');
        Schema::create('conditionally_disableds', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        $conditionallyDisabled = ConditionallyDisabled::first();

        $this->assertNull($conditionallyDisabled);
    }
}

class ConditionallyDisabled extends Model
{
    use Orbital;

    public static function enableOrbit(): bool
    {
        return false;
    }
}
