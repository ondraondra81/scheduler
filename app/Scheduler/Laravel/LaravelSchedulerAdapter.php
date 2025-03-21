<?php

declare(strict_types=1);

namespace App\Scheduler\Laravel;

use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Contract\Task;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\OutputInterface;

readonly class LaravelSchedulerAdapter implements Scheduler
{
    public function __construct(
        private Schedule $schedule,
        private EventMutex|null $eventMutex = null
    ) {
    }

    public function schedule(Task $task): void
    {
        $command = $task->command();
        $event = $task->event();


        $execute = $command->execute();
        if (is_string($execute)) {
            $taskSchedule = $this->schedule->command($execute, $command->parameters());
        } else {
            $taskSchedule = $this->schedule->call($execute, $command->parameters());
        }

        $taskSchedule->cron($event->getExpression());


        foreach ($event->runConditions() as $callback) {
            $taskSchedule->when($callback);
        }

        foreach ($event->skipConditions() as $callback) {
            $taskSchedule->skip($callback);
        }

        $description = $task->getDescription();
        if ($description !== null) {
            $taskSchedule->name($description);
        }

        foreach ($task->beforeCallbacks() as $callback) {
            $taskSchedule->before($callback);
        }

        foreach ($task->successCallbacks() as $callback) {
            $taskSchedule->onSuccess($callback);
        }

        foreach ($task->afterCallbacks() as $callback) {
            $taskSchedule->after($callback);
        }

        foreach ($task->failureCallbacks() as $callback) {
            $taskSchedule->onFailure($callback);
        }

        $ttl = $task->getTTL();
        if (is_int($ttl)) {
            $taskSchedule->withoutOverlapping($ttl);
        }

        // Náhrada Mutexu
        if ($this->eventMutex !== null) {
            $taskSchedule->preventOverlapsUsing($this->eventMutex);
        }

        if ($task->shouldRunOnOneServer()) {
            $taskSchedule->onOneServer();
        }

        if ($task->shouldRunInBackground()) {
            $taskSchedule->runInBackground();
        }
    }

    public function runDueJobs(OutputInterface|null $output = null): void
    {
        Artisan::call(command: 'schedule:run', outputBuffer: $output);
    }
}
