<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use Closure;

interface Event
{
    public function getExpression(): string;

    /**
     * @return array<int, Closure(mixed...): bool>
     */
    public function runConditions(): array;

    /**
     * @return array<int, Closure(mixed...): bool>
     */
    public function skipConditions(): array;
}
