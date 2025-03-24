<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz;

use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Contract\Task;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\OutputInterface;

class ArtisanScheduler implements Scheduler
{
    public function __construct(
        private readonly ScheduleCollection $collection,
        private readonly CrunzScheduleFactory $crunzSchedulerFactory,
        private readonly Kernel $artisan,
    ) {
    }

    public function schedule(Task $task): void
    {
        $schedule = $this->crunzSchedulerFactory->create($task);
        $this->collection->add($schedule);
    }

    public function runDueJobs(OutputInterface|null $output = null): void
    {
        $this->artisan->call(command: 'app:crunz:run', outputBuffer: $output);
    }
}
