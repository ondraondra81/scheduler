<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use Closure;

interface Event
{
    public function getExpression(): string;

    /**
     * @return array<int, Closure>
     */
    public function runConditions(): array;

    /**
     * @return array<int, Closure>
     */
    public function skipConditions(): array;
}
