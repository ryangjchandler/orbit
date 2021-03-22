<?php

namespace Orbit\Drivers;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

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

    public function save(Model $model, string $directory): bool
    {
        $key = $model->getKey();

        if ($model->wasChanged($model->getKeyName())) {
            unlink($directory . DIRECTORY_SEPARATOR . $model->getOriginal($model->getKeyName()) . '.md');
        }

        if (! file_exists($path = $directory . DIRECTORY_SEPARATOR . $key . '.md')) {
            file_put_contents($path, '');
        }

        $matter = array_filter($model->getAttributes(), function ($value, $key) {
            return $key !== 'content' && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        $yaml = Yaml::dump($matter);

        $contents = implode(PHP_EOL, [
            '---',
            rtrim($yaml, PHP_EOL),
            '---',
            $model->getAttribute('content'),
        ]);

        file_put_contents($path, $contents);

        return true;
    }

    public function delete(Model $model, string $directory): bool
    {
        $key = $model->getKey();

        unlink($directory . DIRECTORY_SEPARATOR . $key . '.md');

        return true;
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
