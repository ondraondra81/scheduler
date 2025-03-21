<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

$event = new \App\Scheduler\Event();
$event->everyMinutes(5);

$command = new \App\Scheduler\Command(function (string $foo, string $bar, bool $option, bool $queue) {
    echo(sprintf("Test 3 command foo: %s bar: %s, option: %s, queue: %s", $foo, $bar, $option, $queue));
});
$command->setParameters(['foo' => 'neco', 'bar' => 'nekde', 'option' => true, 'queue' => false]);


$task = new \App\Scheduler\Task(
    $command,
    $event
);

$task->description('Test task 3');
$task->before(function () {
    echo('Test 3 before 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->before(function () {
    echo('Test 3 before 2 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    echo('Test 3 after 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->after(function () {
    echo('Test 3 after 2 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->onSuccess(function () {
    echo('Test 3 Success 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

$task->onFailure(function () {
    echo('Test 3 Failure 1 '  . (new \DateTime())->format('Y-m-d H:i:s'));
});

return $task;
