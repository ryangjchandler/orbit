<?php

namespace Orbit\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Laravel\Scout\ScoutServiceProvider;
use Laravel\Scout\Searchable;
use Orbit\Concerns\Orbital;
use PHPUnit\Framework\Attributes\Test;

class ScoutSupportTest extends TestCase
{
    protected function tearDown(): void
    {
        ScoutPost::all()->each->delete();

        parent::tearDown();
    }

    #[Test]
    public function orbit_models_can_be_searched_with_scout()
    {
        config()->set('scout.driver', 'collection');

        ScoutPost::create([
            'title' => 'Scout post one',
            'content' => 'Post one',
        ]);

        ScoutPost::create([
            'title' => 'Scout post two',
            'content' => 'Post two',
        ]);

        $this->assertCount(2, ScoutPost::search('Post')->get());
        $this->assertCount(1, ScoutPost::search('post two')->get());
    }

    protected function getPackageProviders($app)
    {
        return array_merge([
            ScoutServiceProvider::class,
        ], parent::getPackageProviders($app));
    }
}

class ScoutPost extends Model
{
    use Orbital;
    use Searchable;

    protected $guarded = [];

    public static function schema(Blueprint $table)
    {
        $table->id('id');
        $table->string('title');
        $table->string('content');
    }
}
