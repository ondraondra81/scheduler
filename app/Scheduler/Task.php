<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Contract\Command;
use App\Scheduler\Contract\Event as EventInterface;
use App\Scheduler\Contract\Task as TaskInterface;
use Closure;

class Task implements TaskInterface
{
    private string|null $description = null;

    /**
     * @var array<int, Closure>
     */
    private array $beforeCallbacks = [];
    /**
     * @var array<int, Closure>
     */
    private array $afterCallbacks = [];
    /**
     * @var array<int, Closure>
     */
    private array $successCallbacks = [];
    /**
     * @var array<int, Closure>
     */
    private array $failureCallbacks = [];

    private int|null $ttl = null;

    private string|null $user = null;

    private bool $runOnOneServer = false;
    private bool $runInBackground = false;

    public function __construct(
        private readonly Command $command,
        private readonly EventInterface $event
    ) {
    }

    public function event(): EventInterface
    {
        return $this->event;
    }

    public function command(): Command
    {
        return $this->command;
    }


    public function getTTL(): int|null
    {
        return $this->ttl;
    }

    public function getUser(): string|null
    {
        return $this->user;
    }

    public function user(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function ttl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }


    public function beforeCallbacks(): array
    {
        return $this->beforeCallbacks;
    }

    public function before(Closure $callback): self
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    public function afterCallbacks(): array
    {
        return $this->afterCallbacks;
    }

    public function after(Closure $callback): self
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function successCallbacks(): array
    {
        return $this->successCallbacks;
    }

    public function onSuccess(Closure $callback): self
    {
        $this->successCallbacks[] = $callback;

        return $this;
    }

    public function failureCallbacks(): array
    {
        return $this->failureCallbacks;
    }

    public function onFailure(Closure $callback): self
    {
        $this->failureCallbacks[] = $callback;

        return $this;
    }

    public function runOnOneServer(): self
    {
        $this->runOnOneServer = true;

        return $this;
    }
    public function shouldRunOnOneServer(): bool
    {
        return $this->runOnOneServer;
    }

    public function runInBackground(): self
    {
        $this->runInBackground = true;

        return $this;
    }

    public function shouldRunInBackground(): bool
    {
        return $this->runInBackground;
    }
}
