<?php

declare(strict_types=1);

use App\Scheduler\Command;
use App\Scheduler\Event;
use App\Scheduler\Task;
use Illuminate\Support\Facades\Log;

$event = new Event();
$event->everyMinutes(5);

$command = new Command(function (string $foo, string $bar, bool $option, bool $queue) {
    echo(sprintf("Test 3 command foo: %s bar: %s, option: %s, queue: %s", $foo, $bar, $option, $queue));
});
$command->setParameters(['foo' => 'neco', 'bar' => 'nekde', 'option' => true, 'queue' => false]);


$task = new Task(
    $command,
    $event
);

$task->description('Test task 3');
$task->before(function () {
    Log::info('Test 3 before 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->before(function () {
    Log::info('Test 3 before 2 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    Log::info('Test 3 after 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    Log::info('Test 3 after 2 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->onSuccess(function () {
    Log::info('Test 3 Success 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->onFailure(function () {
    Log::info('Test 3 Failure 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

return $task;
