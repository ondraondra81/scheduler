<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz\Contract;

interface BootHandler
{
    public function boot(): void;
}
