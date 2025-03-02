<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\SoftDeletedPost;

class SoftDeletesTest extends TestCase
{
    protected function tearDown(): void
    {
        SoftDeletedPost::all()->each->forceDelete();

        parent::tearDown();
    }

    public function test_it_will_update_deleted_at_when_deleting()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/soft_deleted_posts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: %s', $post->deleted_at->toIso8601String()), $file);
    }

    public function test_it_will_delete_file_when_force_deleting()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/soft_deleted_posts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: %s', $post->deleted_at->toIso8601String()), $file);

        $post->forceDelete();

        $this->assertFileDoesNotExist(__DIR__.'/content/soft_deleted_posts/'.$post->id.'.md');
    }

    public function test_it_will_remove_deleted_at_when_restoring_file()
    {
        $post = SoftDeletedPost::create([
            'title' => 'Example',
        ]);

        $post->delete();

        $this->assertNotEmpty($post->deleted_at);

        $file = file_get_contents(__DIR__.'/content/soft_deleted_posts/'.$post->id.'.md');

        $this->assertStringContainsString(sprintf('deleted_at: %s', $post->deleted_at->toIso8601String()), $file);

        $post->restore();

        $file = file_get_contents(__DIR__.'/content/soft_deleted_posts/'.$post->id.'.md');

        $this->assertStringNotContainsString('deleted_at', $file);
    }
}
