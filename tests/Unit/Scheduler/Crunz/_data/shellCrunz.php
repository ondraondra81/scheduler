<?php

// Crunz task generovaný automaticky

use Crunz\Schedule;

$schedule = new Schedule();

// Description: Test shell task
$task = $schedule->run(PHP_BINARY . ' backup.php', ['--destination' => 'path/to/destination']);
$task->cron('*/3 * * * *');
$task->description('Test shell task');

return $schedule;
