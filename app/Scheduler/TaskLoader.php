<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Exception\SchedulerException;
use Illuminate\Support\Facades\File;

class TaskLoader
{
    public function __construct(private readonly string $taskDirectory)
    {
    }

    /**
     * @return Task[]
     * @throws SchedulerException
     */
    public function loadTasks(): array
    {
        if (!is_dir($this->taskDirectory)) {
            throw new SchedulerException("Task directory does not exist: {$this->taskDirectory}");
        }

        $taskFiles = File::glob($this->taskDirectory . '/*Task.php');
        $tasks = [];

        foreach ($taskFiles as $file) {
            $task = include $file;

            if (!$task instanceof Task) {
                throw new SchedulerException("File {$file} must return an instance of " . Task::class);
            }

            $tasks[] = $task;
        }

        return $tasks;
    }
}
