<?php

namespace Orbit;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;

final class OrbitOptions
{
    use Macroable;

    private ?string $driver = null;

    private ?string $source = null;

    private ?Closure $generateFilenameUsing = null;

    private bool $enabled = true;

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function driver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function source(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function generateFilenameUsing(Closure $generator): self
    {
        $this->generateFilenameUsing = $generator;

        return $this;
    }

    public static function default(): self
    {
        return self::make();
    }

    public function getDriver(): Driver
    {
        return app($this->driver ?? config('orbit.driver'));
    }

    public function getSource(Model $model): string
    {
        $source = $this->source ?? Str::of($model::class)->classBasename()->snake()->plural();

        if (is_dir($source)) {
            return $source;
        }

        return Orbit::getContentPath() . DIRECTORY_SEPARATOR . $source;
    }

    public function getFilenameGenerator(): ?Closure
    {
        return $this->generateFilenameUsing ?? fn () => '{getKey}';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public static function make(): self
    {
        return new self();
    }
}
