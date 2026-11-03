<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Orbit\Contracts\Orbit;
use Orbit\Index;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CacheCommand extends Command
{
    protected $signature = 'orbit:cache';

    protected $description = 'Build Orbit\'s cache and stop checking content files for changes, ideal for production.';

    public function handle(Index $index): int
    {
        $index->cache($this->models());

        $this->components->info('Orbit cache built. Run `orbit:clear` or `orbit:cache` again after changing content.');

        return self::SUCCESS;
    }

    /** @return array<class-string<Model&Orbit>> */
    private function models(): array
    {
        $paths = array_filter(config('orbit.models', []), 'is_dir');

        if ($paths === []) {
            return [];
        }

        return collect(Finder::create()->in($paths)->files()->name('*.php'))
            ->map(function (SplFileInfo $file) {
                $contents = $file->getContents();

                if (! preg_match('/^namespace\s+([^;\s]+)\s*;/m', $contents, $namespace)) {
                    return null;
                }

                return $namespace[1].'\\'.$file->getBasename('.php');
            })
            ->filter(fn (?string $class) => $class !== null
                && class_exists($class)
                && is_subclass_of($class, Model::class)
                && is_subclass_of($class, Orbit::class)
                && (new ReflectionClass($class))->isInstantiable())
            ->values()
            ->all();
    }
}
