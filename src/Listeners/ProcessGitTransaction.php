<?php

namespace Orbit\Listeners;

use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalForceDeleted;
use Orbit\Events\OrbitalUpdated;
use Orbit\Facades\Orbit;
use Symplify\GitWrapper\GitWrapper;

class ProcessGitTransaction
{
    public function created(OrbitalCreated $event)
    {
        $message = 'orbit: created ' . class_basename($event->model) . ' ' . $event->model->getKey();

        $this->commit($message);
    }

    public function updated(OrbitalUpdated $event)
    {
        $message = 'orbit: updated ' . class_basename($event->model) . ' ' . $event->model->getKey();

        $this->commit($message);
    }

    public function deleted($event)
    {
        if ($event instanceof OrbitalForceDeleted) {
            $message = 'orbit: force deleted ' . class_basename($event->model) . ' ' . $event->model->getKey();
        } else {
            $message = 'orbit: deleted ' . class_basename($event->model) . ' ' . $event->model->getKey();
        }

        $this->commit($message);
    }

    protected function commit(string $message)
    {
        $wrapper = new GitWrapper(
            Orbit::getGitBinary()
        );

        $git = $wrapper->workingCopy(
            Orbit::getGitRoot()
        );

        if (! $git->hasChanges()) {
            return;
        }

        $git->add(
            config('orbit.paths.content') . DIRECTORY_SEPARATOR . '*'
        );

        $git->commit($message);

        $git->push();
    }
}
