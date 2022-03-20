<?php

namespace Orbit;

use Closure;
use Illuminate\Support\Str;
use Orbit\Drivers\Markdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Traits\Macroable;
use Orbit\Contracts\Driver;

final class OrbitOptions
{
    use Macroable;

    private ?string $driver = null;

    private ?string $source = null;

    private ?Closure $generateFilenameUsing = null;

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
        return self::make()
            ->driver(Markdown::class);
    }

    public function getDriver(): Driver
    {
        return app($this->driver);
    }

    public function getSource(Model $model): string
    {
        return $this->source ?? Str::of($model::class)->classBasename()->kebab();
    }

    public function getFilenameGenerator(): ?Closure
    {
        return $this->generateFilenameUsing ?? fn () => '{getKeyName}';
    }

    public static function make(): self
    {
        return new self;
    }
}
