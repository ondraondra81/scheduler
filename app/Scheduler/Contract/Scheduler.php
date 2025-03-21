<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use Symfony\Component\Console\Output\OutputInterface;

interface Scheduler
{
    public function schedule(Task $task): void;

    public function runDueJobs(OutputInterface|null $output = null): void;
}
