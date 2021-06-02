<?php

namespace Orbit\Commands;

use Error;
use Orbit\Facades\Orbit;
use RyanChandler\Git\Git;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CommitCommand extends Command
{
    protected $signature = 'orbit:commit {message?}';

    protected $description = 'Commit and push Orbit content changes to Git.';

    public function handle()
    {
        if (Orbit::isTesting()) {
            return 0;
        }

        /** @var \RyanChandler\Git\Git $git */
        $git = Git::open(
            Orbit::getGitRoot()
        );

        $git->add(
            config('orbit.paths.content')
        );

        try {
            $git->commit(
                $this->argument('message') ?? 'orbit: changes committed manually'
            );
        } catch (ProcessFailedException $e) {
            $this->error('Failed to commit changes. [Message] ' . $e->getMessage());

            return 1;
        }

        $git->push();

        $this->info('Succesfully committed and pushed the latest Orbit content changes.');

        return 0;
    }
}
