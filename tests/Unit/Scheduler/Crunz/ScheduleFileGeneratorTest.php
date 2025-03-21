<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler\Crunz;

use App\Scheduler\Crunz\ScheduleFileGenerator;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use App\Scheduler\Contract\Task;
use App\Scheduler\Contract\Command;
use App\Scheduler\Contract\Event;

class ScheduleFileGeneratorTest extends TestCase
{
    public function testGenerateReturnsValidPhp()
    {
        $closureVar = 'test';
        $closure = function () use ($closureVar) {
            return $closureVar;
        };

        $command = $this->createMock(Command::class);
        $command->method('execute')->willReturn($closure);

        $event = $this->createMock(Event::class);
        $event->method('runConditions')->willReturn([]);

        $task = $this->createMock(Task::class);
        $task->method('command')->willReturn($command);
        $task->method('event')->willReturn($event);
        $task->method('beforeCallbacks')->willReturn([]);
        $task->method('afterCallbacks')->willReturn([]);

        $generator = new ScheduleFileGenerator();
        $phpCode = $generator->generateFileContent($task);

        $this->assertStringContainsString('<?php', $phpCode);
        $this->assertStringContainsString('$schedule', $phpCode);
    }

    public function testGenerateWithBeforeAndAfterCallbacks()
    {
        $command = $this->createMock(Command::class);
        $command->method('execute')->willReturn('app:test-task');

        $event = $this->createMock(Event::class);
        $event->method('runConditions')->willReturn([]);

        $before1 = function () {
            Log::info('Before 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
        };
        $before2 = function () {
            Log::info('Before 2 ' . (new \DateTime())->format('Y-m-d H:i:s'));
        };

        $after1 = function () {
            Log::info('After 1 ' . (new \DateTime())->format('Y-m-d H:i:s'));
        };
        $after2 = function () {
            Log::info('After 2 ' . (new \DateTime())->format('Y-m-d H:i:s'));
        };

        $task = $this->createMock(Task::class);
        $task->method('command')->willReturn($command);
        $task->method('event')->willReturn($event);
        $task->method('beforeCallbacks')->willReturn([$before1, $before2]);
        $task->method('afterCallbacks')->willReturn([$after1, $after2]);

        $generator = new ScheduleFileGenerator();
        $phpCode = $generator->generateFileContent($task);

        $this->assertStringContainsString('Before 1', $phpCode);
        $this->assertStringContainsString('Before 2', $phpCode);
        $this->assertStringContainsString('After 1', $phpCode);
        $this->assertStringContainsString('After 2', $phpCode);
        $this->assertStringContainsString('$schedule', $phpCode);
    }

    public function testGenerateFromRealTaskFileWithArtisanCommand()
    {

        /** @var Task $task */
        $task = include __DIR__ .'/_data/artisanTask.php';;

        $generator = new ScheduleFileGenerator();
        $phpCode = $generator->generateFileContent($task);

        $this->assertStringEqualsFile(__DIR__ .'/_data/artisanCrunz.php', $phpCode);
    }

    public function testGenerateFromRealTaskFileWithShellCommand()
    {

        /** @var Task $task */
        $task = include __DIR__ .'/_data/shellTask.php';;

        $generator = new ScheduleFileGenerator();
        $phpCode = $generator->generateFileContent($task);

        $this->assertStringEqualsFile(__DIR__ .'/_data/shellCrunz.php', $phpCode);
    }

    public function testGenerateFromRealTaskFileWithClosureCommand()
    {

        /** @var Task $task */
        $task = include __DIR__ .'/_data/closureTask.php';;

        $generator = new ScheduleFileGenerator();
        $phpCode = $generator->generateFileContent($task);
        $this->assertStringEqualsFile(__DIR__ .'/_data/closureCrunz.php', $phpCode);
    }
}

