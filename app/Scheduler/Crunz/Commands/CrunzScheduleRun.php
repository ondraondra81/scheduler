<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz\Commands;

use App\Scheduler\Crunz\ScheduleCollection;
use Crunz\EventRunner;
use Crunz\Schedule;
use Crunz\Schedule\ScheduleFactory;
use Crunz\Task\TaskNumber;
use DateTimeZone;
use Illuminate\Console\Command;

class CrunzScheduleRun extends Command
{
    protected $signature = 'app:crunz:run {task? : Which task to run. Provide task number from <info>schedule:list</info> command.} {--f|force : Run all tasks regardless of configured run time.}';

    public function __construct(
        private readonly EventRunner $eventRunner,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly ScheduleCollection $scheduleCollection
    ) {
        parent::__construct();
    }


    public function handle(): int
    {
        require_once __DIR__ . '/../../../../crunz_bin.php';

        $taskId = $this->argument('task');

        $schedules = $this->scheduleCollection->all();


        if (!\count($schedules)) {
            $this->output->writeln('<comment>No task found! Please check your source path.</comment>');

            return 0;
        }

        $tasksTimezone = new DateTimeZone('Europe/Prague'); // @TODO: add to configuration


        // Is specified task should be invoked?
        if (\is_string($taskId)) {
            $schedules = $this->scheduleFactory
                ->singleTaskSchedule(TaskNumber::fromString($taskId), ...$schedules);
        }

        $forceRun = \filter_var($this->options['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $schedules = \array_map(
            static function (Schedule $schedule) use ($tasksTimezone, $forceRun) {
                if (false === $forceRun) {
                    // We keep the events which are due and dismiss the rest.
                    $schedule->events(
                        $schedule->dueEvents(
                            $tasksTimezone
                        )
                    );
                }

                return $schedule;
            },
            $schedules
        );
        $schedules = \array_filter(
            $schedules,
            static fn(Schedule $schedule): bool => \count($schedule->events()) > 0
        );

        if (!\count($schedules)) {
            $this->output->writeln('<comment>No event is due!</comment>');

            return 0;
        }

        // Running the events
        $this->eventRunner
            ->handle($this->output, $schedules);

        return 0;
    }
}
