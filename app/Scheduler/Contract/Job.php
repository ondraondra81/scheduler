<?php

declare(strict_types=1);

namespace App\Scheduler\Contract;

use App\Scheduler\Event;

interface Job
{
    public function event(): Event;
    public function command(): Command;
}
