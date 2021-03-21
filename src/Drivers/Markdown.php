<?php

namespace Orbit\Drivers;

use FilesystemIterator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use SplFileInfo;

class Markdown implements Driver
{
    public function shouldRestoreCache(string $directory): bool
    {
        $highest = 0;

        foreach (new FilesystemIterator($directory) as $file) {
            if ($file->getMTime() > $highest) {
                $highest = $file->getMTime();
            }
        }

        return $highest > filemtime(Orbit::getDatabasePath());
    }

    public function all(string $directory): Collection
    {
        $collection = Collection::make();
        $files = new FilesystemIterator($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') continue;

            $document = YamlFrontMatter::parseFile($file->getPathname());

            $collection->push(array_merge(
                $document->matter(),
                ['content' => trim($document->body())]
            ));
        }

        return $collection;
    }
}
