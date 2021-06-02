<?php

namespace Orbit\Commands;

use Orbit\Facades\Orbit;
use RyanChandler\Git\Git;
use Illuminate\Console\Command;

class PullCommand extends Command
{
    protected $name = 'orbit:pull';

    protected $description = 'Pull the latest Git changes.';

    public function handle()
    {
        if (Orbit::isTesting() || ! config('orbit.git.enabled')) {
            return 0;
        }

        /** @var \RyanChandler\Git\Git $git */
        $git = Git::open(
            Orbit::getGitRoot()
        );

        $git->pull();

        $this->info('Succesfully pulled the latest changes from Git.');

        $path = Orbit::getDatabasePath();

        if (! file_exists($path)) {
            return 0;
        }

        unlink($path);

        $this->info('Succesfully cleared the Orbit cache.');

        return 0;
    }
}
