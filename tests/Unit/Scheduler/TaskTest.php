<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use App\Scheduler\Contract\Command;
use App\Scheduler\Contract\Event as EventInterface;
use App\Scheduler\Task;
use Closure;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    private Command $mockCommand;
    private EventInterface $mockEvent;
    private Task $task;

    protected function setUp(): void
    {
        $this->mockCommand = $this->createMock(Command::class);
        $this->mockEvent = $this->createMock(EventInterface::class);

        $this->task = new Task($this->mockCommand, $this->mockEvent);
    }

    public function testEvent(): void
    {
        self::assertSame($this->mockEvent, $this->task->event());
    }

    public function testCommand(): void
    {
        self::assertSame($this->mockCommand, $this->task->command());
    }

    public function testCanSetAndGetTTL(): void
    {
        self::assertNull($this->task->getTTL());
        $this->task->ttl(3600);
        self::assertSame(3600, $this->task->getTTL());
    }

    public function testCanSetAndGetUser(): void
    {
        self::assertNull($this->task->getUser());
        $this->task->user('testuser');
        self::assertSame('testuser', $this->task->getUser());
    }

    public function testCanSetAndGetDescription(): void
    {
        self::assertNull($this->task->getDescription());
        $this->task->description('Test Description');
        self::assertSame('Test Description', $this->task->getDescription());
    }

    public function testBeforeCallbacksCanBeAddedAndRetrieved(): void
    {
        $callback1 = function () {};
        $callback2 = function () {};

        self::assertEmpty($this->task->beforeCallbacks());

        $this->task->before($callback1);
        $this->task->before($callback2);

        self::assertCount(2, $this->task->beforeCallbacks());
        self::assertSame([$callback1, $callback2], $this->task->beforeCallbacks());
    }

    public function testAfterCallbacksCanBeAddedAndRetrieved(): void
    {
        $callback1 = function () {};
        $callback2 = function () {};

        self::assertEmpty($this->task->afterCallbacks());

        $this->task->after($callback1);
        $this->task->after($callback2);

        self::assertCount(2, $this->task->afterCallbacks());
        self::assertSame([$callback1, $callback2], $this->task->afterCallbacks());
    }

    public function testSuccessCallbacksCanBeAddedAndRetrieved(): void
    {
        $callback = function () {};

        $this->task->onSuccess($callback);

        self::assertCount(1, $this->task->successCallbacks());
        self::assertSame([$callback], $this->task->successCallbacks());
    }

    public function testFailureCallbacksCanBeAddedAndRetrieved(): void
    {
        $callback = function () {};

        $this->task->onFailure($callback);

        self::assertCount(1, $this->task->failureCallbacks());
        self::assertSame([$callback], $this->task->failureCallbacks());
    }

    public function testShouldRunOnOneServer(): void
    {
        self::assertFalse($this->task->shouldRunOnOneServer());
        $this->task->runOnOneServer();
        self::assertTrue($this->task->shouldRunOnOneServer());
    }

    public function testShouldRunInBackground(): void
    {
        self::assertFalse($this->task->shouldRunInBackground());
        $this->task->runInBackground();
        self::assertTrue($this->task->shouldRunInBackground());
    }
}
