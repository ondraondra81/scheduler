<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use Closure;

interface Task
{
    public function getDescription(): ?string;
    public function event(): Event;
    public function command(): Command;

    public function getUser(): string|null;

    public function getTTL(): int|null;

    /**
     * @return array<int, Closure>
     */
    public function beforeCallbacks(): array;
    /**
     * @return array<int, Closure>
     */
    public function afterCallbacks(): array;
    /**
     * @return array<int, Closure>
     */
    public function successCallbacks(): array;
    /**
     * @return array<int, Closure>
     */
    public function failureCallbacks(): array;

    public function shouldRunOnOneServer(): bool;

    public function shouldRunInBackground(): bool;
}
