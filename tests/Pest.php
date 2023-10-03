<?php

use Tests\Fixtures\Models\Category;
use Tests\Fixtures\Models\Post;

uses(Tests\TestCase::class)->in('Feature');

afterEach(function () {
    Post::all()->each(fn (Post $post) => $post->delete());
    Category::all()->each(fn (Category $category) => $category->forceDelete());
});
