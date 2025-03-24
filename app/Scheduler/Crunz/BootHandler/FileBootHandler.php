<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz\BootHandler;

use App\Scheduler\Crunz\Contract\BootHandler;
use App\Scheduler\Crunz\FileScheduler;
use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\TaskLoader;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Filesystem\Filesystem;

class FileBootHandler implements BootHandler
{
    public function __construct(
        private readonly TaskLoader $taskLoader,
        private readonly FileScheduler $scheduler,
        private readonly Repository $cache,
        private readonly Filesystem $filesystem,
        private readonly string $originalTaskDir,
        private readonly string $crunzTaskDir,
    ) {
    }

    public function boot(): void
    {
        $hash = $this->getDirectoryHash($this->originalTaskDir);
        $cachedHash = $this->cache->get('task_directory_hash');

        $crunzTaskDir = $this->crunzTaskDir;
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

        $this->cache->put('task_directory_hash', $hash);
        $this->prepareTaskDirectory($crunzTaskDir);

        $tasks = $this->taskLoader->loadTasks();

        foreach ($tasks as $task) {
            $this->scheduler->schedule($task);
        }
    }

    private function prepareTaskDirectory(string $path): void
    {
        if ($this->filesystem->exists($path)) {
            $this->filesystem->deleteDirectory($path);
        }
        $this->filesystem->makeDirectory($path, 0755, true);
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
}
