<?php
// Crunz task generovaný automaticky

require_once __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Crunz\Schedule;
use Illuminate\Support\Facades\Log;;

$schedule = new Schedule();

// Description: Test task 3
$task = $schedule->run(function () {
    (function (string $foo, string $bar, bool $option, bool $queue) {
        echo sprintf("Test 3 command foo: %s bar: %s, option: %s, queue: %s", $foo, $bar, $option, $queue);
    })('neco', 'nekde', true, false);
}, []);
$task->cron('*/5 * * * *');
$task->description('Test task 3');
$task->before(function () {
    Log::info('Test 3 before 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->before(function () {
    Log::info('Test 3 before 2 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 3 Success 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 3 after 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$task->after(function () {
    Log::info('Test 3 after 2 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});
$schedule->onError(function () {
    Log::info('Test 3 Failure 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
});

return $schedule;
