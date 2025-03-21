<?php

declare(strict_types=1);

namespace App\Providers;

use App\Scheduler\Console\Commands\SchedulerRun;
use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Crunz\CrunzSchedulerAdapter;
use App\Scheduler\Crunz\ScheduleFileGenerator;
use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\TaskLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CruzSchedulerServiceProvider extends ServiceProvider
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
            Scheduler::class,
            function ($app) {
                $crunzDir = config('scheduler.crunz_task_directory', storage_path('crunz'));
                if (!is_string($crunzDir)) {
                    throw new SchedulerException('Crunz task directory must be a string.');
                }
                return new CrunzSchedulerAdapter(
                    $app->make(ScheduleFileGenerator::class),
                    $crunzDir,
                );
            }
        );

        $this->commands([
            SchedulerRun::class,
            ]);
    }

    public function boot(TaskLoader $taskLoader, Scheduler $scheduler): void
    {
        $originalTaskDir = config('scheduler.task_directory');
        if (!is_string($originalTaskDir)) {
            throw new SchedulerException('Task directory must be a string.');
        }
        $crunzTaskDir = config('scheduler.crunz_task_directory', storage_path('crunz'));
        if (!is_string($crunzTaskDir)) {
            throw new SchedulerException('Crunz task directory must be a string.');
        }
        $hash = $this->getDirectoryHash($originalTaskDir);
        $cachedHash = Cache::get('task_directory_hash');

        $glob = glob($crunzTaskDir . '/*');
        if ($glob === false) {
            throw new SchedulerException('Crunz task directory is not readable.');
        }
        if (
            $hash === $cachedHash
            && count($glob) !== 0
        ) {
            return;
        }

        Cache::put('task_directory_hash', $hash);
        $this->prepareTaskDirectory($crunzTaskDir);

        $tasks = $taskLoader->loadTasks();

        foreach ($tasks as $task) {
            $scheduler->schedule($task);
        }
    }

    private function getDirectoryHash(string $directory): string
    {
        $files = glob($directory . '/*.php'); // nebo rekurzivně s RecursiveIterator
        if ($files === false) {
            throw new SchedulerException('Task directory is not readable.');
        }
        $hashData = [];

        foreach ($files as $file) {
            $hashData[] = $file . '|' . filemtime($file);
        }

        return md5(implode("\n", $hashData));
    }

    private function prepareTaskDirectory(string $path): void
    {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        File::makeDirectory($path, 0755, true);
    }
}
