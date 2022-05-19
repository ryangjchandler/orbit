<?php

use Orbit\Tests\TestCase;

use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

uses(TestCase::class)->in(__DIR__);

function assertFileContains(string $file, string $needle): void
{
    assertStringContainsString($needle, file_get_contents($file));
}

function assertFileNotContains(string $file, string $needle): void
{
    assertStringNotContainsString($needle, file_get_contents($file));
}
