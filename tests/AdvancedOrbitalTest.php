<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\CustomKey;
use Orbit\Tests\Fixtures\JsonModel;

class AdvancedOrbitalTest extends TestCase
{
    public function tearDown(): void
    {
        CustomKey::all()->each->delete();
        JsonModel::all()->each->delete();
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

    public function test_it_can_use_a_custom_driver()
    {
        $json = JsonModel::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/json_models/'.$json->getKey().'.json');
    }
}
