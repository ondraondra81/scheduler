<?php

declare(strict_types=1);

use App\Scheduler\Command;
use App\Scheduler\Event;
use App\Scheduler\Task;

$event = new Event();
$event->everyMinutes(3);

$command = new Command(PHP_BINARY . ' backup.php');
$command->setParameters(['--destination' => 'path/to/destination']);

$task = new Task(
    $command,
    $event
);

$task->description('Test shell task');

return $task;
