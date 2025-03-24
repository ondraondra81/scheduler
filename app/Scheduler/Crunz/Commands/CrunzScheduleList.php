<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz\Commands;

use App\Scheduler\Crunz\CrunzScheduleFactory;
use App\Scheduler\Crunz\ScheduleCollection;
use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\TaskLoader;
use Crunz\Exception\CrunzException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class CrunzScheduleList extends Command
{
    private const string FORMAT_TEXT = 'text';
    private const string FORMAT_JSON = 'json';
    /**
     * @var string[]
     */
    private const array FORMATS = [
        self::FORMAT_TEXT,
        self::FORMAT_JSON,
    ];


    public function __construct(
        private readonly ScheduleCollection $scheduleCollection
    ) {
        $possibleFormats = \implode('", "', self::FORMATS);
        $this->signature = "app:crunz:list {format=text : Tasks list format, possible formats: \"{$possibleFormats}\"}";
        parent::__construct();
    }

    public function handle(): int
    {
        $format = $this->resolveFormat();
        $tasks = $this->tasks();
        if (!\count($tasks)) {
            $this->output->writeln('<comment>No task found!</comment>');

            return 0;
        }

        $this->printList(
            $this->output,
            $tasks,
            $format,
        );

        return 0;
    }

    /**
     * @return array<
     *     int,
     *     array{
     *         number: int,
     *         task: string,
     *         expression: string,
     *         command: string,
     *     },
     * >
     */
    private function tasks(): array
    {
        $schedules = $this->scheduleCollection->all();
        $tasksList = [];
        $number = 0;

        foreach ($schedules as $schedule) {
            $events = $schedule->events();
            foreach ($events as $event) {
                $tasksList[] = [
                    'number' => ++$number,
                    'task' => $event->description ?? '',
                    'expression' => $event->getExpression(),
                    'command' => $event->getCommandForDisplay(),
                ];
            }
        }

        return $tasksList;
    }

    private function resolveFormat(): string
    {
        /** @var string $format */
        $format = $this->argument('format');
        dump($format);
        $isValidFormat = in_array(
            $format,
            self::FORMATS,
            true,
        );

        if (false === $isValidFormat) {
            throw new SchedulerException("Format '{$format}' is not supported.");
        }

        return $format;
    }

    /**
     * @param array<
     *         int,
     *         array{
     *         number: int,
     *         task: string,
     *         expression: string,
     *         command: string,
     *         },
     *         > $tasks
     */
    private function printList(
        OutputInterface $output,
        array $tasks,
        string $format,
    ): void {
        switch ($format) {
            case self::FORMAT_TEXT:
                $table = new Table($output);
                $table->setHeaders(
                    [
                        '#',
                        'Task',
                        'Expression',
                        'Command to Run',
                    ]
                );

                foreach ($tasks as $task) {
                    $table->addRow($task);
                }

                $table->render();

                break;

            case self::FORMAT_JSON:
                $output->writeln(
                    \json_encode(
                        $tasks,
                        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
                    ),
                );

                break;

            default:
                throw new CrunzException("Unable to print list in format '{$format}'.");
        }
    }
}
