<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use Closure;

interface Command
{
    /**
     * @return string[]
     */
    public function parameters(): array;

    public function execute(): string|Closure;
}
