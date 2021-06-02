<?php

namespace Orbit\Listeners;

use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalForceDeleted;
use Orbit\Events\OrbitalUpdated;
use Orbit\Facades\Orbit;
use RyanChandler\Git\Git;
use Symplify\GitWrapper\GitWrapper;

class ProcessGitTransaction
{
    public function created(OrbitalCreated $event)
    {
        $this->commit([
            'event' => 'created',
            'model' => class_basename($event->model),
            'primary_key' => $event->model->getKey(),
        ]);
    }

    public function updated(OrbitalUpdated $event)
    {
        $this->commit([
            'event' => 'updated',
            'model' => class_basename($event->model),
            'primary_key' => $event->model->getKey(),
        ]);
    }

    public function deleted($event)
    {
        $this->commit([
            'event' => $event instanceof OrbitalForceDeleted ? 'force deleted' : 'deleted',
            'model' => class_basename($event->model),
            'primary_key' => $event->model->getKey(),
        ]);
    }

    protected function commit(array $keys)
    {
        /** @var \RyanChandler\Git\Git $git */
        $git = Git::open(
            Orbit::getGitRoot()
        );

        if (! $git->hasChanges()) {
            return;
        }

        $git->add(
            config('orbit.paths.content')
        );

        $message = config('orbit.git.message_template');

        foreach ($keys as $search => $replacement) {
            $message = str_replace("{{$search}}", $replacement, $message);
        }

        $git->commit($message);

        $git->push();
    }
}
