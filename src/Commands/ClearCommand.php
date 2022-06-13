<?php

namespace Orbit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orbit\Facades\Orbit;
use Orbit\Support;

class ClearCommand extends Command
{
    protected $signature = 'orbit:clear';

    protected $description = 'Remove Orbit\s database.';

    public function handle()
    {

        $orbitTables = Support::getOrbitalModels()
            // NOTE: Cannot use `new $modelFQN` or `app($modelFQN)` here, because it boots the model and causes a race condition
            // 🛑 So we use a string tranformation to derive the table name, which does account for custom table names.
            ->transform(fn (string $modelFQN) => Str::of(Str::of($modelFQN)->explode('\\')->last())->snake()->plural()->__toString());

        $orbitTables->each(function (string $table) {
            Schema::connection('orbit')->dropIfExists($table);
        });

        $this->info('Cleared Orbit model tables from the sqlite database');

        return 0;
    }
}
