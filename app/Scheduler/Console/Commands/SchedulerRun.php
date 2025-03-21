<?php

declare(strict_types=1);

namespace App\Scheduler\Console\Commands;

use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Laravel\LaravelSchedulerAdapter;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;

class SchedulerRun extends Command
{
    protected $signature = 'app:scheduler:run';
    protected $description = 'Spustí naplánované úlohy pomocí scheduler adapteru.';

    protected Scheduler $scheduler;

    public function __construct(Scheduler $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    public function handle(): void
    {
        $output = new BufferedOutput();
        $this->info('Spouštím naplánované úlohy...');
        $this->scheduler->runDueJobs($output);
        $this->info($output->fetch());
        $this->info('Úlohy dokončeny.');
    }
}
