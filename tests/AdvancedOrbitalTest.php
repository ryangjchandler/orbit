<?php

namespace Orbit\Tests;

use Orbit\Tests\Fixtures\CustomKey;

class AdvancedOrbitalTest extends TestCase
{
    public function tearDown(): void
    {
        CustomKey::all()->each->delete();
    }

    public function test_it_can_create_files_using_custom_primary_key()
    {
        $custom = CustomKey::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/customkeys/'.$custom->name.'.md');
    }

    public function test_it_can_update_files_using_custom_primary_key()
    {
        $custom = CustomKey::create([
            'name' => 'Ryan',
        ]);

        $this->assertFileExists(__DIR__.'/content/customkeys/'.$custom->name.'.md');

        $custom->update([
            'name' => 'John',
        ]);

        $this->assertFileDoesNotExist(__DIR__.'/content/customkeys/Ryan.md');
        $this->assertFileExists(__DIR__.'/content/customkeys/'.$custom->name.'.md');
    }
}
