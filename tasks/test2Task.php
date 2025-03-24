<?php

declare(strict_types=1);

use App\Scheduler\Command;
use App\Scheduler\Event;
use App\Scheduler\Task;
use Illuminate\Support\Facades\Log;

$event = new Event();
$event->everyMinutes(1);

$command = new Command('app:test-task');
$command->setParameters(['foo' => 'foobar', '--option']);


$task = new Task(
    $command,
    $event
);

$task->description('Test task 2');
$task->before(function () {
    Log::info('Test 2 before 1 '  . (new DateTime())->format('Y-m-d H:i:s'));
});
$task->before(function () {
    Log::info('Test 2 before 2 '  . (new DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    Log::info('Test 2 after 1 '  . (new DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    Log::info('Test 2 after 2 '  . (new DateTime())->format('Y-m-d H:i:s'));
});

$task->onSuccess(function () {
    Log::info('Test 2 Success 1 '  . (new DateTime())->format('Y-m-d H:i:s'));
});

$task->onFailure(function () {
    Log::info('Test 2 Failure 1 '  . (new DateTime())->format('Y-m-d H:i:s'));
});

return $task;
