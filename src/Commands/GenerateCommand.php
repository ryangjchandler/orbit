<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GenerateCommand extends Command
{
    protected $signature = 'orbit:generate {model}';

    protected $description = 'Quickly create a new Orbit record.';

    public function handle()
    {
        $model = $this->qualifyModelClass();
        $data = $this->getColumnsFromModel($model)->mapWithKeys(function (ColumnDefinition $column, string $name): array {
            $value = $this->ask(Str::title($name));

            if ($column->get('nullable', false) && ($value === '' || $value === 'null')) {
                $value = null;
            } elseif ($value === 'true' && $column->get('type') === 'boolean') {
                $value = true;
            } elseif ($value === 'false' && $column->get('type') === 'boolean') {
                $value = false;
            }

            return [$name => $value];
        })->all();

        /**
         * @psalm-suppress InvalidStringClass
         */
        $model::create($data);

        $this->info('Model created successfully.');
    }

    protected function getColumnsFromModel(string $modelClass): Collection
    {
        $blueprint = new Blueprint('__orbit:generate__');

        /**
         * @psalm-suppress InvalidStringClass
         */
        $modelClass::schema($blueprint);

        return collect($blueprint->getColumns())->mapWithKeys(function (ColumnDefinition $column): array {
            return [
                $column->getAttributes()['name'] => $column,
            ];
        });
    }

    protected function qualifyModelClass(): string
    {
        $rootNamespace = app()->getNamespace();

        $prefixes = [
            $rootNamespace,
            $rootNamespace.'Models\\',
        ];

        foreach ($prefixes as $prefix) {
            $namespaced = $prefix.$this->argument('model');

            if (class_exists($namespaced)) {
                return $namespaced;
            }
        }

        $this->error('Model does not exist.');

        exit(1);
    }
}
