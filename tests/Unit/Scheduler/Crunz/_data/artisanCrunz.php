<?php
// Crunz task generovaný automaticky

require_once __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Crunz\Schedule;
use Illuminate\Support\Facades\Log;;

$schedule = new Schedule();

// Description: Test task 2
$task = $schedule->run(PHP_BINARY . ' artisan app:test-task', ['foo' => 'foobar', '--option']);
$task->cron('*/3 * * * *');
$task->description('Test task 2');
$task->before(function () {
    Log::info('Test 2 before 1 ' . (new DateTime())->format('Y-m-d H:i:s'));
});
$task->before(function () {
    Log::info('Test 2 before 2 ' . (new DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 2 Success 1 ' . (new DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 2 after 1 ' . (new DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 2 after 2 ' . (new DateTime())->format('Y-m-d H:i:s'));
});
$schedule->onError(function () {
    Log::info('Test 2 Failure 1 ' . (new DateTime())->format('Y-m-d H:i:s'));
});

return $schedule;
