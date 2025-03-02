<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\Emptiness;
use Orbit\Tests\Fixtures\Post;

class BasicOrbitalTest extends TestCase
{
    public function tearDown(): void
    {
        Post::all()->each->delete();

        parent::tearDown();
    }

    public function test_it_will_return_an_empty_collection_when_no_content_present()
    {
        $this->assertTrue(Emptiness::all()->isEmpty());
    }

    public function test_it_will_create_a_new_file_when_creating_model()
    {
        $post = Post::create([
            'title' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/posts/'.$post->id.'.md');
    }

    public function test_it_will_return_existing_file()
    {
        $post = Post::create([
            'title' => 'Example',
        ]);

        $retreived = Post::find($post->id);

        $this->assertTrue($post->is($retreived));
    }

    public function test_it_will_write_to_existing_file()
    {
        $post = Post::create([
            'title' => 'Example',
        ]);

        $post->update([
            'title' => 'Amazing',
        ]);

        $contents = file_get_contents(__DIR__.'/content/posts/'.$post->id.'.md');

        $this->assertStringContainsString('Amazing', $contents);
    }

    public function test_it_will_delete_existing_files()
    {
        $post = Post::create([
            'title' => 'Delete',
        ]);

        $post->delete();

        $this->assertFileDoesNotExist(__DIR__.'/content/posts/'.$post->id.'.md');
    }
}
