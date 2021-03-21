<?php

namespace Orbit\Drivers;

use FilesystemIterator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class Markdown implements Driver
{
    public function shouldRestoreCache(string $directory): bool
    {
        return filemtime($directory) > filemtime(Orbit::getDatabasePath());
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
                ['content' => $document->body()]
            ));
        }

        return $collection;
    }
}
