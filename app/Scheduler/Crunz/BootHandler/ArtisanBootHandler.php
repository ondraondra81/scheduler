<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz\BootHandler;

use App\Scheduler\Crunz\ArtisanScheduler;
use App\Scheduler\Crunz\Contract\BootHandler;
use App\Scheduler\TaskLoader;

class ArtisanBootHandler implements BootHandler
{
    public function __construct(
        private readonly TaskLoader $taskLoader,
        private readonly ArtisanScheduler $scheduler
    ) {
    }

    public function boot(): void
    {
        $tasks = $this->taskLoader->loadTasks();

        foreach ($tasks as $task) {
            $this->scheduler->schedule($task);
        }
    }
}
