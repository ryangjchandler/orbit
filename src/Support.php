<?php

namespace Orbit;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Orbit\Contracts\Driver;
use Orbit\Contracts\IsOrbital;
use Orbit\Concerns\Orbital;
use Orbit\Facades\Orbit;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;

/** @internal */
final class Support
{
    public static function generateFilename(Model & IsOrbital $object, OrbitOptions $options, Driver $driver): string
    {
        $pattern = app()->call($options->getFilenameGenerator());

        return Str::of($pattern)
            ->explode('/')
            ->map(static function (string $part) use ($object): string {
                if (!Str::startsWith($part, '{') && !Str::endsWith($part, '}')) {
                    return $part;
                }

                $part = Str::of($part)->trim('{}');

                [$source, $arg] = $part->contains(':') ?
                    $part->explode(':', 2)->all() :
                    [$part->toString(), null];

                if (method_exists($object, $source)) {
                    return $object->{$source}();
                }

                $property = $object->{$source};

                if ($property instanceof DateTimeInterface) {
                    return $arg ? $property->format($arg) : $property->format($object->getDateFormat());
                }

                return $property;
            })
            ->implode('/') . '.' . $driver->extension();
    }

    public static function callTraitMethods(Model & IsOrbital $object, string $prefix, array $args = []): void
    {
        $called = [];

        foreach (class_uses_recursive($object) as $trait) {
            $method = $prefix . class_basename($trait);

            if (!method_exists($object, $method) || in_array($method, $called)) {
                continue;
            }

            $object->{$method}(...$args);

            $called[] = $method;
        }
    }

    public static function fileNeedsToBeSeeded(string $path, string $modelClass): bool
    {
        $changedTime = filemtime($path);
        $modelFile = (new ReflectionClass($modelClass))->getFileName();

        return $changedTime > filemtime($modelFile) || $changedTime > filemtime(Orbit::getCachePath());
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
    public static function modelNeedsMigration(string $modelClass): bool
    {
        $modelFile = (new ReflectionClass($modelClass))->getFileName();

        if (filemtime($modelFile) > filemtime(Orbit::getCachePath())) {
            return true;
        }

        $table = (new $modelClass())->getTable();

        if (!$modelClass::resolveConnection()->getSchemaBuilder()->hasTable($table)) {
            return true;
        }

        return false;
    }

    public static function getOrbitalModels(): Collection
    {
        $files = Finder::create()
            ->in(App::basePath('app'))
            ->files()
            ->name("*.php")
            ->contains('implements IsOrbital');

        $models = collect(iterator_to_array($files))
            ->map(function (FinderSplFileInfo $file) {
                if (preg_match('#^namespace\s+(.+?);.*class\s+(\w+).+;$#sm', file_get_contents($file->getPathname()), $m)) {
                    $namespace = $m[1];
                    $className = $m[2];
                    $classFQN = '\\' . $namespace . '\\' . $className;
                }

                return $classFQN ?? null;
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

        return collect($models);
    }
}
