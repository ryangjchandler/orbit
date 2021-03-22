<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\SoftDeletedPost;

class SoftDeletesTest extends TestCase
{
    public function test_it_will_update_deleted_at_when_deleting()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/softdeletedposts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: \'%s\'', $post->deleted_at), $file);
    }

    public function test_it_will_delete_file_when_force_deleting()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/softdeletedposts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: \'%s\'', $post->deleted_at), $file);

        $post->forceDelete();

        $this->assertFileDoesNotExist(__DIR__.'/content/softdeletedposts/'.$post->id.'.md');
    }

    public function test_it_will_remove_deleted_at_when_restoring_file()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/softdeletedposts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: \'%s\'', $post->deleted_at), $file);

        $post->restore();

        $file = file_get_contents(__DIR__.'/content/softdeletedposts/'.$post->id.'.md');

        $this->assertStringNotContainsString('deleted_at', $file);
    }
}
