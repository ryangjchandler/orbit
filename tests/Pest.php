<?php

use Tests\Fixtures\Models\Category;
use Tests\Fixtures\Models\FlatJsonModel;
use Tests\Fixtures\Models\Post;

uses(Tests\TestCase::class)->in('Feature');

afterEach(function () {
    Post::all()->each(fn(Post $post) => $post->delete());
    FlatJsonModel::all()->each(fn(FlatJsonModel $model) => $model->delete());
    Category::all()->each(fn(Category $category) => $category->forceDelete());
});
