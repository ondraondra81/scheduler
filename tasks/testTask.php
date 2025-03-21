<?php

declare(strict_types=1);


$event = new \App\Scheduler\Event();
$event->everyMinute();

$command = new \App\Scheduler\Command('app:test-task');


$task = new \App\Scheduler\Task(
    $command,
    $event
);

$task->description('Test task');
$task->before(function () {
    echo('before 1');
});
$task->before(function () {
    echo('before 2');
});

$task->after(function () {
    echo('after 1');
});

$task->after(function () {
    echo('after 2');
});

$task->onSuccess(function () {
    echo('Success 1');
});

$task->onFailure(function () {
    echo('Failure 1');
});

return $task;
