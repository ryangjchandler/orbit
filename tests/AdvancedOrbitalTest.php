<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\Post;
use Orbit\Tests\Fixtures\CustomKey;
use Orbit\Tests\Fixtures\JsonModel;
use Orbit\Tests\Fixtures\MarkdownJsonModel;
use Orbit\Tests\Fixtures\YamlModel;

class AdvancedOrbitalTest extends TestCase
{
    public function tearDown(): void
    {
        CustomKey::all()->each->delete();
        JsonModel::all()->each->delete();
        YamlModel::all()->each->delete();
        Post::all()->each->delete();
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

    public function test_it_can_use_markdown_json_driver()
    {
        $md = MarkdownJsonModel::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/markdown_json_models/'.$md->getKey().'.md');
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
}
