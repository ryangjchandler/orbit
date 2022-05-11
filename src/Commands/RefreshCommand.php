<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orbit\Facades\Orbit;

class RefreshCommand extends Command
{
    protected $signature = 'orbit:refresh';

    protected $description = 'Remove the Orbit database and rebuild it from the file sources.';

    public function handle()
    {
        Artisan::call('orbit:clear');

        // `new` up each model to trigger bootOrbital, which will migrate and seed
        $this->findOrbitModels()->each(fn (string $modelString) => new $modelString());
    }

    protected function findOrbitModels(): Collection
    {
        $laravel = $this->getLaravel();

        return collect(File::allFiles($laravel->path()))
            ->map(function ($item) use ($laravel) {
                // Convert file path to namespace
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    $laravel->getNamespace().'\\',
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );

                return $class;
            })
            ->filter(function ($class) {
                if (! class_exists($class)) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class) &&
                    ! $reflection->isAbstract() &&
                    isset(class_uses_recursive($class)[Orbital::class]);
            });
    }
}
