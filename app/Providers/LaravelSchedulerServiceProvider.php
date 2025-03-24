<?php

declare(strict_types=1);

namespace App\Providers;

use App\Scheduler\Console\Commands\SchedulerRun;
use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\FileMutex;
use App\Scheduler\Laravel\LaravelSchedulerAdapter;
use App\Scheduler\TaskLoader;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class LaravelSchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->singleton(
            TaskLoader::class,
            function () {
                $taskDirectory = config('scheduler.task_directory');
                if (!is_string($taskDirectory)) {
                    throw new SchedulerException('Task directory must be a string.');
                }
                if (!is_dir($taskDirectory)) {
                    throw new SchedulerException('Task directory does not exist.');
                }

                return new TaskLoader(
                    $taskDirectory
                );
            }
        );

        $this->app->singleton(
            FileMutex::class,
            function () {
                $path = config('scheduler.cache_directory');
                if (!is_string($path)) {
                    throw new SchedulerException('Path directory must be a string.');
                }
                return new FileMutex($path);
            }
        );
        $this->app->singleton(
            Scheduler::class,
            function (Application $app) {
                return new LaravelSchedulerAdapter(
                    $app->make(Schedule::class),
                    $app->get(FileMutex::class)
                );
            }
        );

        $this->commands(
            [
            SchedulerRun::class,
            ]
        );
    }

    public function boot(TaskLoader $taskLoader, Scheduler $scheduler): void
    {
        $tasks = $taskLoader->loadTasks();

        foreach ($tasks as $task) {
            $scheduler->schedule($task);
        }
    }
}
