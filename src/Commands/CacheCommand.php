<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Orbit\Contracts\Orbit;
use Orbit\Index;
use Symfony\Component\Finder\Finder;

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
        if (! is_dir(app_path('Models'))) {
            return [];
        }

        return collect(Finder::create()->in(app_path('Models'))->files()->name('*.php'))
            ->map(fn ($file) => $this->laravel->getNamespace().str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR),
            ))
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class) && is_subclass_of($class, Orbit::class))
            ->values()
            ->all();
    }
}
