<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orbit\Concerns\Orbital;
use ReflectionClass;

class RefreshCommand extends Command
{
    protected $signature = 'orbit:refresh';

    protected $description = 'Remove the Orbit database and then rebuild it from the content source files.';

    public function handle()
    {
        Artisan::call('orbit:clear');

        // New up each model to trigger bootOrbital, which will migrate and force seed
        $this->findOrbitModels()->each(fn (string $modelFQN) => new $modelFQN());
    }

    protected function findOrbitModels(): Collection
    {
        $laravel = $this->getLaravel();

        return collect(File::allFiles($laravel->path()))
            ->map(function ($item) use ($laravel) {
                // Convert file path to namespace
                $path = $item->getRelativePathName();

                return '\\' . sprintf(
                    '%s%s',
                    $laravel->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );
            })
            ->filter(function ($class) {
                if (!class_exists($class)) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class) &&
                    !$reflection->isAbstract() &&
                    isset(class_uses_recursive($class)[Orbital::class]);
            });
    }
}
