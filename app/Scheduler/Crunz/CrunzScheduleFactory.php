<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz;

use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\Contract\Task;
use Closure;
use Crunz\Schedule;

class CrunzScheduleFactory
{
    public function create(Task $task): Schedule
    {
        $schedule = new Schedule();
        $execute = $task->command()->execute();
        $parameters = $task->command()->parameters();
        if (count($parameters) > 0 && $execute instanceof Closure) {
            $execute = fn() => $execute(...$parameters);
            $scheduleEvent = $schedule->run($execute);
        }

        if (is_string($execute)) {
            if (preg_match('/^[a-z0-9:_-]+$/i', $execute)) {
                $scheduleEvent = $schedule->run(PHP_BINARY . ' artisan ' . $execute, $parameters);
            } else {
                $scheduleEvent = $schedule->run($execute, $parameters);
            }
        }

        if (!isset($scheduleEvent)) {
            throw new SchedulerException('Not implement execute type');
        }

        $event = $task->event();

        $scheduleEvent->cron($event->getExpression());
        $description = $task->getDescription();
        if ($description !== null) {
            $scheduleEvent->name($description);
        }

        foreach ($event->runConditions() as $callback) {
            $scheduleEvent->when($callback);
        }

        foreach ($event->skipConditions() as $callback) {
            $scheduleEvent->skip($callback);
        }

        foreach ($task->beforeCallbacks() as $callback) {
            $scheduleEvent->before($callback);
        }

        foreach ($task->successCallbacks() as $callback) {
            $scheduleEvent->after($callback);
        }

        foreach ($task->afterCallbacks() as $callback) {
            $scheduleEvent->after($callback);
        }

        foreach ($task->failureCallbacks() as $callback) {
            $schedule->onError($callback);
        }

        $ttl = $task->getTTL();
        if ($ttl !== null) {
            $scheduleEvent->preventOverlapping();
        }
        $user = $task->getUser();
        if ($user !== null) {
            $scheduleEvent->user($user);
        }

        return $schedule;
    }
}
