<?php

use Orbit\Tests\TestCase;

use function PHPUnit\Framework\assertStringContainsString;

uses(TestCase::class)->in(__DIR__);

function assertFileContains(string $file, string $needle): void
{
    assertStringContainsString($needle, file_get_contents($file));
}
