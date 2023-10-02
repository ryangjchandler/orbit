<?php

use Tests\Fixtures\Models\Post;

it('will seed from source files newer than the database', function () {
    file_put_contents(base_path('content/posts/9999.md'), <<<'MD'
    ---
    id: 9999
    title: 'Fresh Post'
    ---
    MD);

    $post = Post::find(9999);

    expect($post)
        ->toBeInstanceOf(Post::class)
        ->id->toBe(9999);
});
