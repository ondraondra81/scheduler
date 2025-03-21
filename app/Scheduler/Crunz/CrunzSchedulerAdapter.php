<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz;

use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Contract\Task;
use Crunz\Schedule;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CrunzSchedulerAdapter implements Scheduler
{
    public function __construct(
        private readonly ScheduleFileGenerator $scheduleFileGenerator,
        private readonly string $taskFilePath
    ) {
    }

    public function schedule(Task $task): void
    {
        $name = $this->normalizeFileName($task->getDescription() ?? Uuid::uuid4()->toString());

        $fileContent = $this->scheduleFileGenerator->generateFileContent($task);

        file_put_contents($this->taskFilePath . '/' . $name . 'Crunz.php', $fileContent);
    }

    public function runDueJobs(OutputInterface|null $output = null): void
    {
        $process = new Process(['vendor/bin/crunz', 'schedule:run']);
        $process->enableOutput();
        $process->run();
        $cmd = $process->getCommandLine();
        $output?->writeln($cmd);
        $output?->writeln($process->getOutput());
    }


    // reseni pouze pro ted. normalne bych na to pouzil uz neco hotoveho.
    private function normalizeFileName(string $description): string
    {
        $normalized = mb_strtolower($description, 'UTF-8');

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $normalized);
        if (false === $normalized) {
            return Uuid::uuid4()->toString();
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
        if ($normalized === null) {
            return Uuid::uuid4()->toString();
        }

        return trim($normalized, '-');
    }
}
